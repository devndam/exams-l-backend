<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceVerificationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['candidate_id', 'session_id', 'similarity', 'passed', 'image_hash', 'ip_address'];

    protected $casts = [
        'created_at' => 'datetime',
        'passed' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }
}
