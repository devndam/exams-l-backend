<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateExamAssignment extends Model
{
    public const CREATED_AT = 'assigned_at';

    public const UPDATED_AT = null;

    protected $fillable = ['candidate_id', 'exam_type_id', 'status'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }
}
