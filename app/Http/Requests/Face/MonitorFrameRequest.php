<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;

class MonitorFrameRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'string', 'min:100', 'starts_with:data:image/'],
            'sessionId' => ['required', 'integer', 'min:1'],
            'frameNumber' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.min' => 'Image data is required',
            'image.starts_with' => 'Invalid image data URI format',
            'sessionId.min' => 'Invalid session ID',
            'frameNumber.min' => 'Invalid frame number',
        ];
    }
}
