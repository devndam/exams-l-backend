<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;
use App\Support\Constants;
use Illuminate\Validation\Rule;

class MonitorTerminateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'sessionId' => ['required', 'integer', 'min:1'],
            'cause' => ['required', Rule::in(['face', 'audio'])],
            'eventType' => ['required', Rule::in([
                Constants::FACE_EVENT_NO_FACE,
                Constants::FACE_EVENT_MULTIPLE_FACES,
                Constants::FACE_EVENT_FACE_MISMATCH,
                Constants::FACE_EVENT_PROCESSING_ERROR,
                Constants::AUDIO_EVENT_NOISE_DETECTED,
                Constants::AUDIO_EVENT_VOICE_DETECTED,
            ])],
            'metric' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'sessionId.min' => 'Invalid session ID',
            'cause.in' => 'Cause must be face or audio',
        ];
    }
}
