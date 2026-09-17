<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioMonitoringEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['session_id', 'event_type', 'decibel'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }
}
