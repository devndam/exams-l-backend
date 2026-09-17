<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceEnrollment extends Model
{
    public const CREATED_AT = 'enrolled_at';

    protected $fillable = [
        'candidate_id', 'embedding_encrypted', 'embedding_iv', 'embedding_auth_tag',
        'embedding_dimension', 'capture_image', 'status', 'reviewed_at', 'review_note',
    ];

    // embedding_encrypted is raw ciphertext bytes (OPENSSL_RAW_DATA) — not valid UTF-8,
    // so json_encode() throws if it's ever serialized. All three are internal storage
    // details that should never reach an API response anyway.
    protected $hidden = ['embedding_encrypted', 'embedding_iv', 'embedding_auth_tag'];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
