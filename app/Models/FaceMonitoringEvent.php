<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceMonitoringEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['session_id', 'event_type', 'similarity', 'frame_number'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }
}
