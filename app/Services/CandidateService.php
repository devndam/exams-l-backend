<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Candidate;
use App\Models\CandidateExamAssignment;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Support\Constants;
use App\Support\NotificationMailer;
use Illuminate\Database\Eloquent\Collection;

class CandidateService
{
    public function __construct(private readonly NotificationMailer $mailer) {}

    public function createCandidate(array $data): Candidate
    {
        if (Candidate::query()->where('candidate_id', $data['candidate_id'])->exists()) {
            throw new ApiException(409, 'A candidate with this ID already exists');
        }
        if (Candidate::query()->where('email', $data['email'])->exists()) {
            throw new ApiException(409, 'A candidate with this email already exists');
        }

        $candidate = Candidate::query()->create($data);
        $this->mailer->notifyCandidateWelcome($candidate);

        return $candidate;
    }

    public function getCandidates(): Collection
    {
        return Candidate::query()->withCount('assignments')->orderByDesc('created_at')->get();
    }

    public function getCandidateById(int $id): Candidate
    {
        $candidate = Candidate::query()->with([
            'assignments' => fn ($q) => $q->with('examType')->orderByDesc('assigned_at'),
            'sessions' => fn ($q) => $q->whereNotNull('completed_at')->with('examType:id,name')->orderByDesc('completed_at'),
            'faceEnrollment:candidate_id,status,enrolled_at,reviewed_at,review_note',
        ])->find($id);

        if (! $candidate) {
            throw new ApiException(404, 'Candidate not found');
        }

        $candidate->sessions->each(function (ExamSession $session) {
            $session->percentage = $session->total_questions > 0
                ? (int) round(($session->score / $session->total_questions) * 100)
                : 0;
        });

        return $candidate;
    }

    /** @param array<int, int> $examTypeIds */
    public function assignExam(int $candidateId, array $examTypeIds): Collection
    {
        $candidate = Candidate::query()->find($candidateId);
        if (! $candidate) {
            throw new ApiException(404, 'Candidate not found');
        }

        $foundIds = ExamType::query()->whereIn('id', $examTypeIds)->pluck('id')->all();
        $missingIds = array_diff($examTypeIds, $foundIds);
        if ($missingIds) {
            throw new ApiException(404, 'Exam type(s) not found: '.implode(', ', $missingIds));
        }

        $alreadyAssigned = CandidateExamAssignment::query()
            ->where('candidate_id', $candidateId)->whereIn('exam_type_id', $examTypeIds)
            ->pluck('exam_type_id')->all();
        $toAssign = array_diff($examTypeIds, $alreadyAssigned);

        if (! $toAssign) {
            throw new ApiException(409, 'All selected exams are already assigned to this candidate');
        }

        foreach ($toAssign as $examTypeId) {
            CandidateExamAssignment::query()->create([
                'candidate_id' => $candidateId,
                'exam_type_id' => $examTypeId,
                'status' => Constants::EXAM_STATUS_PENDING,
            ]);
        }

        return CandidateExamAssignment::query()
            ->where('candidate_id', $candidateId)->whereIn('exam_type_id', $toAssign)
            ->with(['examType', 'candidate'])->get();
    }

    public function unassignExam(int $candidateId, int $examTypeId): void
    {
        $assignment = $this->findAssignment($candidateId, $examTypeId);

        // The exam_sessions/candidate_answers FKs cascade on delete, so removing the
        // assignment's sessions here is enough to clean up their answers too.
        ExamSession::query()->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)->delete();
        $assignment->delete();
    }

    public function resetExam(int $candidateId, int $examTypeId): void
    {
        $assignment = $this->findAssignment($candidateId, $examTypeId);
        if ($assignment->status === Constants::EXAM_STATUS_PENDING) {
            throw new ApiException(400, 'Exam is already pending');
        }

        ExamSession::query()->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)->delete();
        $assignment->update(['status' => Constants::EXAM_STATUS_PENDING]);
    }

    public function getCandidateExams(int $candidateId): Collection
    {
        $assignments = CandidateExamAssignment::query()
            ->where('candidate_id', $candidateId)->with('examType')->orderByDesc('assigned_at')->get();

        $canceledIds = $assignments->where('status', 'canceled')->pluck('exam_type_id')->all();

        if ($canceledIds) {
            $sessions = ExamSession::query()
                ->where('candidate_id', $candidateId)
                ->whereIn('exam_type_id', $canceledIds)
                ->where(fn ($q) => $q->where('terminated_by_face', true)->orWhere('terminated_by_audio', true))
                ->orderByDesc('started_at')
                ->get(['exam_type_id', 'termination_reason', 'terminated_by_face', 'terminated_by_audio']);

            $byExamType = $sessions->keyBy('exam_type_id');

            foreach ($assignments as $assignment) {
                if ($assignment->status === 'canceled') {
                    $session = $byExamType->get($assignment->exam_type_id);
                    $assignment->termination_reason = $session->termination_reason ?? null;
                    $assignment->terminated_by_face = $session->terminated_by_face ?? false;
                    $assignment->terminated_by_audio = $session->terminated_by_audio ?? false;
                }
            }
        }

        return $assignments;
    }

    public function deleteCandidate(int $id): void
    {
        $candidate = Candidate::query()->find($id);
        if (! $candidate) {
            throw new ApiException(404, 'Candidate not found');
        }

        // exam_sessions/candidate_exam_assignments/face_enrollments etc. all cascade on delete.
        $candidate->delete();
    }

    public function updateCertificateDetails(int $candidateId, array $details): Candidate
    {
        $candidate = Candidate::query()->findOrFail($candidateId);
        $candidate->update([
            'certificate_name' => $details['certificate_name'],
            'certificate_email' => $details['certificate_email'],
            'certificate_phone' => $details['certificate_phone'],
        ]);

        return $candidate;
    }

    private function findAssignment(int $candidateId, int $examTypeId): CandidateExamAssignment
    {
        $assignment = CandidateExamAssignment::query()
            ->where('candidate_id', $candidateId)->where('exam_type_id', $examTypeId)->first();

        if (! $assignment) {
            throw new ApiException(404, 'Assignment not found');
        }

        return $assignment;
    }
}
