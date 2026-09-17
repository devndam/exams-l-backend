<?php

namespace App\Support;

class Constants
{
    public const EXAM_STATUS_PENDING = 'pending';
    public const EXAM_STATUS_IN_PROGRESS = 'in_progress';
    public const EXAM_STATUS_COMPLETED = 'completed';
    public const EXAM_STATUS_CANCELED = 'canceled';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CANDIDATE = 'candidate';

    public const OPTIONS = ['A', 'B', 'C', 'D'];

    public const FACE_EVENT_OK = 'ok';
    public const FACE_EVENT_NO_FACE = 'no_face';
    public const FACE_EVENT_MULTIPLE_FACES = 'multiple_faces';
    public const FACE_EVENT_FACE_MISMATCH = 'face_mismatch';
    public const FACE_EVENT_PROCESSING_ERROR = 'processing_error';

    public const AUDIO_EVENT_OK = 'ok';
    public const AUDIO_EVENT_NOISE_DETECTED = 'noise_detected';
    public const AUDIO_EVENT_VOICE_DETECTED = 'voice_detected';
}
