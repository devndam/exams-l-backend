<?php

namespace App\Services\Face;

use App\Events\AudioMonitoringEventOccurred;
use App\Events\FaceMonitoringEventOccurred;
use App\Events\SessionTerminated;
use App\Exceptions\ApiException;
use App\Models\AudioMonitoringEvent;
use App\Models\Candidate;
use App\Models\CandidateExamAssignment;
use App\Models\ExamSession;
use App\Models\FaceEnrollment;
use App\Models\FaceLivenessSession;
use App\Models\FaceMonitoringEvent;
use App\Models\FaceVerificationLog;
use App\Services\Face\Contracts\FaceEngine;
use App\Support\Constants;
use App\Support\FaceEmbeddingCrypto;
use App\Support\NotificationMailer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaceService
{
    public function __construct(
        private readonly FaceEngine $engine,
        private readonly FaceEmbeddingCrypto $crypto,
        private readonly NotificationMailer $mailer,
    ) {}

    private function parseImageDataUri(string $dataUri): string
    {
        if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/', $dataUri, $matches)) {
            throw new ApiException(400, 'Invalid image data URI');
        }

        return base64_decode($matches[2]);
    }

    private function dataUriToBinary(string $dataUri): string
    {
        $parts = explode(',', $dataUri, 2);
        if (! isset($parts[1])) {
            throw new ApiException(500, 'Enrolled face image is malformed');
        }

        return base64_decode($parts[1]);
    }

    // ─── Enrollment ──────────────────────────────────────────────────────────

    public function enrollFace(int $candidateId, string $imageDataUri, array $embedding): array
    {
        $imageBinary = $this->parseImageDataUri($imageDataUri);
        $imageHash = hash('sha256', $imageBinary);

        $encrypted = $this->crypto->encrypt($embedding);
        $embeddingData = [
            'embedding_encrypted' => $encrypted['encrypted'],
            'embedding_iv' => $encrypted['iv'],
            'embedding_auth_tag' => $encrypted['authTag'],
            'embedding_dimension' => count($embedding),
        ];

        $enrollData = array_merge([
            'capture_image' => $imageDataUri,
            'status' => 'pending',
            'reviewed_at' => null,
            'review_note' => null,
        ], $embeddingData);

        $enrollment = FaceEnrollment::query()->updateOrCreate(['candidate_id' => $candidateId], $enrollData);

        Log::info("Face enrolled for candidate {$candidateId}, imageHash={$imageHash}, dimension={$embeddingData['embedding_dimension']}");

        $candidate = Candidate::query()->find($candidateId);
        if ($candidate) {
            $this->mailer->notifyAdminFaceCapture($candidate);
        }

        return [
            'enrolled' => true,
            'dimension' => $embeddingData['embedding_dimension'] ?? null,
            'enrolledAt' => $enrollment->enrolled_at,
        ];
    }

    // ─── Verification ────────────────────────────────────────────────────────

    /**
     * @param  array<float>  $embedding  the client-computed live embedding — accepted and validated
     *                                    (must be 128-d) but not itself used to decide the verdict, see
     *                                    class docblock: the client's $matched/$distance are authoritative.
     */
    public function verifyFace(int $candidateId, string $imageDataUri, array $embedding, bool $matched, float $distance, ?int $sessionId = null, ?string $ipAddress = null): array
    {
        $imageBinary = $this->parseImageDataUri($imageDataUri);

        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();
        if (! $enrollment) {
            throw new ApiException(400, 'Face not enrolled. Please complete face enrollment first.');
        }
        if ($enrollment->status !== 'approved') {
            throw new ApiException(400, 'Face enrollment has not been approved yet.');
        }

        $imageHash = hash('sha256', $imageBinary);
        $threshold = config('exams.face.verification_threshold');

        FaceVerificationLog::query()->create([
            'candidate_id' => $candidateId,
            'session_id' => $sessionId,
            'similarity' => $distance,
            'passed' => $matched,
            'image_hash' => $imageHash,
            'image' => $matched ? null : $imageDataUri,
            'ip_address' => $ipAddress,
        ]);

        Log::info("Face verify candidate={$candidateId}: distance=".round($distance, 4).", passed=".($matched ? 'true' : 'false'));

        return [
            'verified' => $matched,
            'similarity' => round(max(0, (1 - $distance) * 100), 1),
            'distance' => round($distance, 4),
            'threshold' => $threshold,
            'engine' => 'client',
        ];
    }

    /**
     * @return array{embedding: array<float>, dimension: int, threshold: float}
     */
    public function getReferenceEmbedding(int $candidateId): array
    {
        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();
        if (! $enrollment) {
            throw new ApiException(404, 'Face not enrolled. Please complete face enrollment first.');
        }
        if ($enrollment->status !== 'approved') {
            throw new ApiException(400, 'Face enrollment has not been approved yet.');
        }
        if (! $enrollment->embedding_encrypted) {
            throw new ApiException(500, 'Face embedding not found. Please re-enroll.');
        }

        $storedEmbedding = $this->crypto->decrypt(
            $enrollment->embedding_encrypted,
            $enrollment->embedding_iv,
            $enrollment->embedding_auth_tag,
        );

        return [
            'embedding' => $storedEmbedding,
            'dimension' => $enrollment->embedding_dimension,
            'threshold' => config('exams.face.verification_threshold'),
        ];
    }

    // ─── Monitoring ──────────────────────────────────────────────────────────

    /**
     * @param  array<float>|null  $embedding  the client-computed live embedding for this frame, present
     *                                         only when exactly one face was detected — stored as forensic
     *                                         evidence alongside the frame image on non-OK events, never
     *                                         used to re-derive $eventType (the client's classification is
     *                                         authoritative, see class docblock).
     */
    public function processMonitoringFrame(int $candidateId, int $sessionId, string $imageDataUri, int $frameNumber, string $eventType, ?float $distance, ?array $embedding): array
    {
        $session = ExamSession::query()->find($sessionId);
        if (! $session) {
            throw new ApiException(404, 'Session not found');
        }
        if ($session->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if ($session->completed_at) {
            throw new ApiException(400, 'Session already completed');
        }

        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();
        if (! $enrollment) {
            throw new ApiException(400, 'Face not enrolled');
        }

        $action = match ($eventType) {
            Constants::FACE_EVENT_OK => 'none',
            Constants::FACE_EVENT_MULTIPLE_FACES => 'flag',
            default => 'warning',
        };

        Log::info("Monitoring session={$sessionId} candidate={$candidateId} frame={$frameNumber} event={$eventType}".
            ($distance !== null ? ' distance='.round($distance, 4) : ''));

        $isFailure = $eventType !== Constants::FACE_EVENT_OK;

        FaceMonitoringEvent::query()->create([
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'similarity' => $distance,
            'frame_number' => $frameNumber,
            'image' => $isFailure ? $imageDataUri : null,
            'embedding' => $isFailure ? $embedding : null,
        ]);

        $warningCount = null;
        if (in_array($action, ['warning', 'flag'], true)) {
            $session->increment('face_warning_count');
            $warningCount = $session->fresh()->face_warning_count;
            if ($warningCount >= config('exams.face.max_warnings')) {
                $action = 'terminate';
            }
        }

        if ($action === 'terminate') {
            $reason = match ($eventType) {
                Constants::FACE_EVENT_FACE_MISMATCH => "Exam canceled: A different face was detected repeatedly (distance: {$distance}). The face on camera did not match the enrolled face.",
                Constants::FACE_EVENT_MULTIPLE_FACES => 'Exam canceled: Multiple faces were detected on camera repeatedly during the exam.',
                default => 'Exam canceled: Face could not be detected on camera repeatedly during the exam.',
            };
            $this->terminateSession($sessionId, $candidateId, $session->exam_type_id, $reason);
            Log::warning("Session {$sessionId} canceled after repeated warnings: {$eventType}");

            SessionTerminated::dispatch($sessionId, $candidateId, $eventType);
        }

        $result = [
            'eventType' => $eventType,
            'similarity' => $distance !== null ? round($distance, 4) : null,
            'action' => $action,
            'warningCount' => $warningCount,
        ];

        FaceMonitoringEventOccurred::dispatch($sessionId, $candidateId, $eventType, $distance, $action, $warningCount);

        return $result;
    }

    public function processAudioEvent(int $candidateId, int $sessionId, string $eventType, ?float $decibel): array
    {
        $session = ExamSession::query()->find($sessionId);
        if (! $session) {
            throw new ApiException(404, 'Session not found');
        }
        if ($session->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if ($session->completed_at) {
            throw new ApiException(400, 'Session already completed');
        }

        AudioMonitoringEvent::query()->create([
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'decibel' => $decibel,
        ]);

        $action = 'warning';
        $session->increment('audio_warning_count');
        $warningCount = $session->fresh()->audio_warning_count;

        if ($warningCount >= config('exams.audio.max_warnings')) {
            $action = 'terminate';

            $reason = $eventType === Constants::AUDIO_EVENT_VOICE_DETECTED
                ? 'Exam canceled: Multiple voices were repeatedly detected during the exam.'
                : 'Exam canceled: Excessive background noise was repeatedly detected during the exam.';

            $this->terminateSessionByAudio($sessionId, $candidateId, $session->exam_type_id, $reason);
            Log::warning("Session {$sessionId} terminated by audio: {$eventType}, dB: {$decibel}");

            SessionTerminated::dispatch($sessionId, $candidateId, $eventType);
        }

        $result = ['eventType' => $eventType, 'decibel' => $decibel, 'action' => $action, 'warningCount' => $warningCount];

        AudioMonitoringEventOccurred::dispatch($sessionId, $candidateId, $eventType, $decibel, $action, $warningCount);

        return $result;
    }

    private function terminateSession(int $sessionId, int $candidateId, int $examTypeId, string $reason): void
    {
        DB::transaction(function () use ($sessionId, $candidateId, $examTypeId, $reason) {
            $score = DB::table('candidate_answers')->where('session_id', $sessionId)->where('is_correct', true)->count();

            ExamSession::query()->whereKey($sessionId)->update([
                'completed_at' => now(),
                'score' => $score,
                'terminated_by_face' => true,
                'termination_reason' => $reason,
            ]);

            CandidateExamAssignment::query()
                ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)
                ->update(['status' => Constants::EXAM_STATUS_CANCELED]);
        });
    }

    private function terminateSessionByAudio(int $sessionId, int $candidateId, int $examTypeId, string $reason): void
    {
        DB::transaction(function () use ($sessionId, $candidateId, $examTypeId, $reason) {
            $score = DB::table('candidate_answers')->where('session_id', $sessionId)->where('is_correct', true)->count();

            ExamSession::query()->whereKey($sessionId)->update([
                'completed_at' => now(),
                'score' => $score,
                'terminated_by_audio' => true,
                'termination_reason' => $reason,
            ]);

            CandidateExamAssignment::query()
                ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)
                ->update(['status' => Constants::EXAM_STATUS_CANCELED]);
        });
    }

    // ─── Liveness ────────────────────────────────────────────────────────────

    public function createLivenessSession(int $candidateId, string $purpose): array
    {
        $result = $this->engine->createLivenessSession();

        FaceLivenessSession::query()->create([
            'candidate_id' => $candidateId,
            'aws_session_id' => $result['sessionId'],
            'purpose' => $purpose,
        ]);

        return ['sessionId' => $result['sessionId']];
    }

    public function getLivenessCredentials(): array
    {
        return $this->engine->getLivenessCredentials();
    }

    public function completeLivenessSession(int $candidateId, string $awsSessionId, string $purpose): array
    {
        $dbSession = FaceLivenessSession::query()->where('aws_session_id', $awsSessionId)->first();
        if (! $dbSession) {
            throw new ApiException(404, 'Liveness session not found');
        }
        if ($dbSession->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if ($dbSession->status !== 'pending') {
            throw new ApiException(400, 'Liveness session already completed');
        }

        $result = $this->engine->getLivenessResult($awsSessionId);
        $threshold = config('exams.face.liveness_threshold', 75);

        $passed = $result->status === 'SUCCEEDED' && $result->confidence !== null && $result->confidence >= $threshold;
        $referenceImage = $result->referenceImageBinary
            ? 'data:image/jpeg;base64,'.base64_encode($result->referenceImageBinary)
            : null;

        $dbSession->update([
            'status' => $passed ? 'completed' : 'failed',
            'confidence' => $result->confidence,
            'passed' => $passed,
            'reference_image' => $referenceImage,
            'completed_at' => now(),
        ]);

        if (! $passed) {
            return [
                'livenessPassed' => false,
                'confidence' => $result->confidence !== null ? round($result->confidence, 1) : null,
                'threshold' => $threshold,
            ];
        }

        if ($purpose === 'enrollment' && $referenceImage) {
            FaceEnrollment::query()->updateOrCreate(['candidate_id' => $candidateId], [
                'capture_image' => $referenceImage,
                'status' => 'pending',
                'reviewed_at' => null,
                'review_note' => null,
                'embedding_encrypted' => null,
                'embedding_iv' => null,
                'embedding_auth_tag' => null,
                'embedding_dimension' => null,
            ]);

            $candidate = Candidate::query()->find($candidateId);
            if ($candidate) {
                $this->mailer->notifyAdminFaceCapture($candidate);
            }

            Log::info("Liveness enrollment: candidate={$candidateId}, confidence={$result->confidence}");

            return [
                'livenessPassed' => true,
                'enrolled' => true,
                'confidence' => round($result->confidence, 1),
                'threshold' => $threshold,
            ];
        }

        if ($purpose === 'verification') {
            $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();
            if (! $enrollment || ! $enrollment->capture_image) {
                throw new ApiException(400, 'No enrolled face found. Please enroll first.');
            }
            if ($enrollment->status !== 'approved') {
                throw new ApiException(400, 'Face enrollment has not been approved yet.');
            }
            if (! $result->referenceImageBinary) {
                throw new ApiException(500, 'Face service did not return a reference image for verification.');
            }

            $enrolledBinary = $this->dataUriToBinary($enrollment->capture_image);
            $comparison = $this->engine->compareFaces($enrolledBinary, $result->referenceImageBinary);

            FaceVerificationLog::query()->create([
                'candidate_id' => $candidateId,
                'similarity' => $comparison->similarity,
                'passed' => $comparison->matched,
            ]);

            Log::info("Liveness verification: candidate={$candidateId}, confidence={$result->confidence}, similarity={$comparison->similarity}, matched=".($comparison->matched ? 'true' : 'false'));

            return [
                'livenessPassed' => true,
                'livenessConfidence' => round($result->confidence, 1),
                'verified' => $comparison->matched,
                'similarity' => round($comparison->similarity, 1),
                'threshold' => config('exams.face.reference_threshold', 90),
            ];
        }

        return ['livenessPassed' => true, 'confidence' => round($result->confidence, 1)];
    }

    // ─── Admin helpers ───────────────────────────────────────────────────────

    public function getLivenessSessions(?int $candidateId = null): Collection
    {
        return FaceLivenessSession::query()
            ->when($candidateId, fn ($q) => $q->where('candidate_id', $candidateId))
            ->with(['candidate:id,candidate_id,full_name,email'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getEnrollmentStatus(int $candidateId): array
    {
        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();

        return [
            'enrolled' => (bool) $enrollment,
            'status' => $enrollment->status ?? null,
            'enrolledAt' => $enrollment->enrolled_at ?? null,
            'updatedAt' => $enrollment->updated_at ?? null,
            'reviewNote' => $enrollment->review_note ?? null,
        ];
    }

    public function getSessionMonitoringEvents(int $sessionId): Collection
    {
        return FaceMonitoringEvent::query()->where('session_id', $sessionId)->orderBy('created_at')->get();
    }

    public function getFlaggedSessions(): Collection
    {
        return ExamSession::query()
            ->where(fn ($q) => $q->where('terminated_by_face', true)->orWhere('terminated_by_audio', true))
            ->with([
                'candidate:id,candidate_id,full_name,email',
                'examType:id,name',
                'monitoringEvents',
                'audioEvents',
            ])
            ->orderByDesc('completed_at')
            ->get();
    }

    public function getPendingEnrollments(): Collection
    {
        return FaceEnrollment::query()
            ->where('status', 'pending')
            ->with(['candidate:id,candidate_id,full_name,email'])
            ->orderByDesc('enrolled_at')
            ->get(['id', 'candidate_id', 'capture_image', 'status', 'enrolled_at']);
    }

    public function getCaptureImage(int $candidateId): array
    {
        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first(['capture_image', 'status']);
        if (! $enrollment) {
            throw new ApiException(404, 'Face enrollment not found for this candidate');
        }

        return ['captureImage' => $enrollment->capture_image, 'status' => $enrollment->status];
    }

    public function resetEnrollment(int $candidateId): array
    {
        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();
        if (! $enrollment) {
            throw new ApiException(404, 'Face enrollment not found for this candidate');
        }

        $wasStatus = $enrollment->status;
        $enrollment->delete();

        Log::info("Face enrollment reset for candidate {$candidateId} (was status={$wasStatus})");

        return ['reset' => true];
    }

    public function reviewEnrollment(int $candidateId, string $status, ?string $reviewNote = null): FaceEnrollment
    {
        $enrollment = FaceEnrollment::query()->where('candidate_id', $candidateId)->first();
        if (! $enrollment) {
            throw new ApiException(404, 'Face enrollment not found for this candidate');
        }

        $enrollment->update([
            'status' => $status,
            'reviewed_at' => now(),
            'review_note' => $status === 'rejected' ? $reviewNote : null,
        ]);

        Log::info("Face enrollment {$status} for candidate {$candidateId}".($reviewNote ? ": {$reviewNote}" : ''));

        return $enrollment->load('candidate:id,candidate_id,full_name');
    }
}
