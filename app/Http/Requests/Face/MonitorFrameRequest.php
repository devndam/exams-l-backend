<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;
use App\Support\Constants;
use Illuminate\Validation\Rule;

class MonitorFrameRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'string', 'min:100', 'starts_with:data:image/'],
            'sessionId' => ['required', 'integer', 'min:1'],
            'frameNumber' => ['required', 'integer', 'min:0'],
            'eventType' => ['required', Rule::in([
                Constants::FACE_EVENT_OK,
                Constants::FACE_EVENT_NO_FACE,
                Constants::FACE_EVENT_MULTIPLE_FACES,
                Constants::FACE_EVENT_FACE_MISMATCH,
                Constants::FACE_EVENT_PROCESSING_ERROR,
            ])],
            'distance' => ['nullable', 'numeric', 'min:0'],
            'embedding' => ['nullable', 'array', 'size:128'],
            'embedding.*' => ['numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.min' => 'Image data is required',
            'image.starts_with' => 'Invalid image data URI format',
            'sessionId.min' => 'Invalid session ID',
            'frameNumber.min' => 'Invalid frame number',
            'embedding.size' => 'Face embedding must have exactly 128 dimensions',
        ];
    }
}
