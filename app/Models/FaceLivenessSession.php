<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceLivenessSession extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'candidate_id', 'aws_session_id', 'purpose', 'status',
        'confidence', 'passed', 'reference_image', 'completed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
        'passed' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
