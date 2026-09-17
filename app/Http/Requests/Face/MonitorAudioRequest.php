<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;

/**
 * REST replacement for the old `/audio-monitor` socket namespace's `audio-violation`
 * event. Unlike the socket (which learned sessionId once via an `init` handshake and
 * kept it in connection state), each REST call is stateless and must carry it.
 */
class MonitorAudioRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'sessionId' => ['required', 'integer', 'min:1'],
            'eventType' => ['required', 'in:noise_detected,voice_detected'],
            'decibel' => ['sometimes', 'nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'sessionId.min' => 'Invalid session ID',
            'eventType.in' => 'Event type must be noise_detected or voice_detected',
        ];
    }
}
