<?php

namespace App\Services;

use App\Events\SessionTerminated;
use App\Exceptions\ApiException;
use App\Models\Candidate;
use App\Models\CandidateAnswer;
use App\Models\CandidateExamAssignment;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Models\Question;
use App\Services\Face\FaceService;
use App\Support\Constants;
use App\Support\NotificationMailer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionService
{
    public function __construct(
        private readonly FaceService $faceService,
        private readonly NotificationMailer $mailer,
    ) {}

    private function completeAbandonedSession(int $sessionId, int $candidateId, int $examTypeId): void
    {
        DB::transaction(function () use ($sessionId, $candidateId, $examTypeId) {
            $score = CandidateAnswer::query()->where('session_id', $sessionId)->where('is_correct', true)->count();

            ExamSession::query()->whereKey($sessionId)->update(['completed_at' => now(), 'score' => $score]);

            CandidateExamAssignment::query()
                ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)
                ->update(['status' => Constants::EXAM_STATUS_COMPLETED]);
        });
    }

    public function startExam(int $candidateId, int $examTypeId, ?string $liveFaceImage = null, ?array $embedding = null, ?bool $matched = null, ?float $distance = null): array
    {
        $assignment = CandidateExamAssignment::query()
            ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)->first();

        if (! $assignment) {
            throw new ApiException(404, 'Exam not assigned to this candidate');
        }
        if ($assignment->status === Constants::EXAM_STATUS_COMPLETED) {
            throw new ApiException(400, 'Exam already completed');
        }
        if ($assignment->status === Constants::EXAM_STATUS_CANCELED) {
            throw new ApiException(400, 'Exam has been canceled due to a face verification violation');
        }

        if ($assignment->status === Constants::EXAM_STATUS_IN_PROGRESS) {
            $existingSession = ExamSession::query()
                ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)
                ->whereNull('completed_at')->first();

            if ($existingSession) {
                $this->completeAbandonedSession($existingSession->id, $candidateId, $examTypeId);
                throw new ApiException(400, 'Your previous session was incomplete and has been marked as completed. Please contact admin to reset the exam if needed.');
            }
        }

        $activeSession = ExamSession::query()->where('candidate_id', $candidateId)->whereNull('completed_at')->first();
        if ($activeSession) {
            throw new ApiException(400, 'Another exam session is already active. Complete it first.');
        }

        $examType = ExamType::query()->find($examTypeId);
        if (! $examType) {
            throw new ApiException(404, 'Exam type not found');
        }

        if ($examType->requires_face_verification) {
            $enrollment = DB::table('face_enrollments')->where('candidate_id', $candidateId)->first();
            if (! $enrollment) {
                throw new ApiException(400, 'Face enrollment is required before starting an exam. Please complete face capture first.');
            }
            if ($enrollment->status !== 'approved') {
                throw new ApiException(400, 'Your face enrollment must be approved by an admin before you can start an exam.');
            }
            if (! $liveFaceImage) {
                throw new ApiException(400, 'Live face verification is required to start this exam. Please allow camera access.');
            }
            if ($embedding === null || $matched === null || $distance === null) {
                throw new ApiException(400, 'Face verification failed. Please ensure your face is clearly visible and try again.');
            }

            try {
                $verifyResult = $this->faceService->verifyFace($candidateId, $liveFaceImage, $embedding, $matched, $distance);
            } catch (\Throwable $e) {
                Log::warning("Pre-exam face verification failed for candidate {$candidateId}: {$e->getMessage()}");
                throw $e;
            }

            if (! $verifyResult['verified']) {
                Log::warning("Pre-exam face MISMATCH for candidate {$candidateId}: distance=".($verifyResult['distance'] ?? null).", threshold=".($verifyResult['threshold'] ?? null));
                throw new ApiException(403, 'Face verification failed. The person on camera does not match the enrolled face. Exam cannot be started.');
            }
            Log::info("Pre-exam face verification passed for candidate {$candidateId}: distance=".($verifyResult['distance'] ?? null));
        }

        $allIds = Question::query()->where('exam_type_id', $examTypeId)->pluck('id')->all();

        if (count($allIds) < $examType->total_questions) {
            throw new ApiException(400, "Not enough questions in question bank. Need {$examType->total_questions}, have ".count($allIds));
        }

        shuffle($allIds);
        $selectedIds = array_slice($allIds, 0, $examType->total_questions);
        shuffle($selectedIds);

        $session = DB::transaction(function () use ($candidateId, $examTypeId, $selectedIds, $examType) {
            $newSession = ExamSession::query()->create([
                'candidate_id' => $candidateId,
                'exam_type_id' => $examTypeId,
                'shuffled_question_ids' => array_values($selectedIds),
                'current_question_index' => 0,
                'total_questions' => $examType->total_questions,
            ]);

            CandidateExamAssignment::query()
                ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)
                ->update(['status' => Constants::EXAM_STATUS_IN_PROGRESS]);

            return $newSession;
        });

        return [
            'sessionId' => $session->id,
            'totalQuestions' => $session->total_questions,
            'currentQuestionIndex' => 0,
            'timePerQuestion' => $examType->time_per_question,
            'requiresFaceVerification' => $examType->requires_face_verification,
            'resumed' => false,
        ];
    }

    public function getCurrentQuestion(int $sessionId, int $candidateId): array
    {
        $session = ExamSession::query()->find($sessionId);
        if (! $session) {
            throw new ApiException(404, 'Session not found');
        }
        if ($session->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if ($session->completed_at) {
            throw new ApiException(400, 'Exam already completed');
        }

        $questionIds = $session->shuffled_question_ids;
        if ($session->current_question_index >= count($questionIds)) {
            throw new ApiException(400, 'No more questions');
        }

        $questionId = $questionIds[$session->current_question_index];
        $question = Question::query()->find($questionId, [
            'id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
        ]);

        return [
            'question' => $question,
            'currentIndex' => $session->current_question_index,
            'totalQuestions' => $session->total_questions,
        ];
    }

    public function submitAnswer(int $sessionId, int $candidateId, int $questionId, ?string $selectedOption): array
    {
        $session = ExamSession::query()->find($sessionId);
        if (! $session) {
            throw new ApiException(404, 'Session not found');
        }
        if ($session->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if ($session->completed_at) {
            throw new ApiException(400, 'Exam already completed');
        }

        $questionIds = $session->shuffled_question_ids;
        $expectedQuestionId = $questionIds[$session->current_question_index] ?? null;

        if ($questionId !== $expectedQuestionId) {
            throw new ApiException(400, 'Invalid question for current position');
        }

        $existingAnswer = CandidateAnswer::query()->where('session_id', $sessionId)->where('question_id', $questionId)->first();
        if ($existingAnswer) {
            throw new ApiException(400, 'Question already answered');
        }

        $question = Question::query()->findOrFail($questionId);
        $isCorrect = $selectedOption !== null && $selectedOption === $question->correct_option;
        $isLastQuestion = $session->current_question_index >= $session->total_questions - 1;

        return DB::transaction(function () use ($sessionId, $candidateId, $questionId, $selectedOption, $isCorrect, $isLastQuestion, $session, $questionIds) {
            CandidateAnswer::query()->create([
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'selected_option' => $selectedOption,
                'is_correct' => $isCorrect,
            ]);

            $nextIndex = $session->current_question_index + 1;

            if ($isLastQuestion) {
                $score = CandidateAnswer::query()->where('session_id', $sessionId)->where('is_correct', true)->count();

                ExamSession::query()->whereKey($sessionId)->update([
                    'current_question_index' => $nextIndex,
                    'completed_at' => now(),
                    'score' => $score,
                ]);

                CandidateExamAssignment::query()
                    ->where('candidate_id', $candidateId)->where('exam_type_id', $session->exam_type_id)
                    ->update(['status' => Constants::EXAM_STATUS_COMPLETED]);

                $candidate = Candidate::query()->find($candidateId);
                $examType = ExamType::query()->find($session->exam_type_id, ['name']);
                if ($candidate && $examType) {
                    $this->mailer->notifyAdminExamCompleted($candidate, $examType->name, $score, $session->total_questions);
                }

                return ['isLast' => true, 'score' => $score, 'totalQuestions' => $session->total_questions];
            }

            ExamSession::query()->whereKey($sessionId)->update(['current_question_index' => $nextIndex]);

            $nextQuestionId = $questionIds[$nextIndex];
            $nextQuestion = Question::query()->find($nextQuestionId, [
                'id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
            ]);

            return [
                'isLast' => false,
                'nextIndex' => $nextIndex,
                'nextQuestion' => $nextQuestion,
                'totalQuestions' => $session->total_questions,
            ];
        });
    }

    public function cancelSession(int $sessionId, int $candidateId, string $reason): array
    {
        $session = ExamSession::query()->find($sessionId);
        if (! $session) {
            throw new ApiException(404, 'Session not found');
        }
        if ($session->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if ($session->completed_at) {
            throw new ApiException(400, 'Exam already completed');
        }

        $message = match ($reason) {
            'tab_switch' => 'Exam canceled: candidate switched tabs or windows during the exam.',
            default => 'Exam canceled: candidate logged out while the exam was in progress.',
        };

        DB::transaction(function () use ($sessionId, $candidateId, $session, $message) {
            $score = CandidateAnswer::query()->where('session_id', $sessionId)->where('is_correct', true)->count();

            ExamSession::query()->whereKey($sessionId)->update([
                'completed_at' => now(),
                'score' => $score,
                'termination_reason' => $message,
            ]);

            CandidateExamAssignment::query()
                ->where('candidate_id', $candidateId)->where('exam_type_id', $session->exam_type_id)
                ->update(['status' => Constants::EXAM_STATUS_CANCELED]);
        });

        SessionTerminated::dispatch($sessionId, $candidateId, $reason);

        return ['canceled' => true];
    }

    public function getSessionResult(int $sessionId, int $candidateId): array
    {
        $session = ExamSession::query()->with([
            'examType',
            'answers' => fn ($q) => $q->with(['question:id,question_text,option_a,option_b,option_c,option_d,correct_option'])->orderBy('answered_at'),
        ])->find($sessionId);

        if (! $session) {
            throw new ApiException(404, 'Session not found');
        }
        if ($session->candidate_id !== $candidateId) {
            throw new ApiException(403, 'Access denied');
        }
        if (! $session->completed_at) {
            throw new ApiException(400, 'Exam not yet completed');
        }

        $percentage = $session->total_questions > 0 ? (int) round(($session->score / $session->total_questions) * 100) : 0;

        return [
            'sessionId' => $session->id,
            'examName' => $session->examType->name,
            'score' => $session->score,
            'totalQuestions' => $session->total_questions,
            'percentage' => $percentage,
            'startedAt' => $session->started_at,
            'completedAt' => $session->completed_at,
            'answers' => $session->answers,
        ];
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

    public function getActiveSessions(): Collection
    {
        return ExamSession::query()
            ->whereNull('completed_at')
            ->with(['candidate:id,candidate_id,full_name', 'examType:id,name,requires_face_verification'])
            ->orderByDesc('started_at')
            ->get();
    }
}
