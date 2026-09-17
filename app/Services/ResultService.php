<?php

namespace App\Services;

use App\Models\ExamSession;
use Illuminate\Support\Collection;

class ResultService
{
    public function getAllResults(?int $examTypeId, ?int $candidateId): Collection
    {
        $sessions = ExamSession::query()
            ->whereNotNull('completed_at')
            ->when($examTypeId, fn ($q) => $q->where('exam_type_id', $examTypeId))
            ->when($candidateId, fn ($q) => $q->where('candidate_id', $candidateId))
            ->with([
                'candidate:id,candidate_id,full_name,email',
                'examType:id,name',
            ])
            ->orderByDesc('completed_at')
            ->get();

        return $sessions->map(fn (ExamSession $s) => [
            'sessionId' => $s->id,
            'candidate' => $s->candidate,
            'examType' => $s->examType,
            'score' => $s->score,
            'totalQuestions' => $s->total_questions,
            'percentage' => $s->total_questions > 0 ? (int) round(($s->score / $s->total_questions) * 100) : 0,
            'startedAt' => $s->started_at,
            'completedAt' => $s->completed_at,
            'terminatedByFace' => $s->terminated_by_face,
            'terminatedByAudio' => $s->terminated_by_audio,
            'terminationReason' => $s->termination_reason,
        ]);
    }
}
