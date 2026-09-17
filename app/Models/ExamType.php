<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    protected $fillable = ['name', 'total_questions', 'time_per_question', 'requires_face_verification'];

    protected $casts = [
        'requires_face_verification' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CandidateExamAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }
}
