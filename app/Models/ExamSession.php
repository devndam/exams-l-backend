<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    public const CREATED_AT = 'started_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'candidate_id', 'exam_type_id', 'shuffled_question_ids', 'current_question_index',
        'completed_at', 'score', 'total_questions', 'termination_reason',
        'terminated_by_face', 'face_warning_count', 'terminated_by_audio', 'audio_warning_count',
    ];

    protected $casts = [
        'shuffled_question_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'terminated_by_face' => 'boolean',
        'terminated_by_audio' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CandidateAnswer::class, 'session_id');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(FaceVerificationLog::class, 'session_id');
    }

    public function monitoringEvents(): HasMany
    {
        return $this->hasMany(FaceMonitoringEvent::class, 'session_id')->orderBy('created_at');
    }

    public function audioEvents(): HasMany
    {
        return $this->hasMany(AudioMonitoringEvent::class, 'session_id')->orderBy('created_at');
    }
}
