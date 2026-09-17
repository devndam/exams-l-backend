<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Candidate extends Model
{
    protected $fillable = [
        'candidate_id', 'full_name', 'email',
        'certificate_name', 'certificate_email', 'certificate_phone',
        'photo_url', 'session_token',
    ];

    protected $hidden = ['session_token'];

    public function assignments(): HasMany
    {
        return $this->hasMany(CandidateExamAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function faceEnrollment(): HasOne
    {
        return $this->hasOne(FaceEnrollment::class);
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(FaceVerificationLog::class);
    }

    public function livenessSessions(): HasMany
    {
        return $this->hasMany(FaceLivenessSession::class);
    }
}
