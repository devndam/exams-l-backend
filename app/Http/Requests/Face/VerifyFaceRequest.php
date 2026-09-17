<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;

class VerifyFaceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'string', 'min:100', 'starts_with:data:image/'],
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric'],
            'matched' => ['required', 'boolean'],
            'distance' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.min' => 'Image data is required',
            'image.starts_with' => 'Invalid image data URI format',
            'embedding.size' => 'Face embedding must have exactly 128 dimensions',
        ];
    }
}
