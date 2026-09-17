<?php

namespace App\Http\Requests\Session;

use App\Http\Requests\ApiFormRequest;

class StartExamRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'liveFaceImage' => ['sometimes', 'nullable', 'regex:/^data:image\/(png|jpeg|jpg|webp);base64,/'],
            'embedding' => ['sometimes', 'nullable', 'array', 'size:128'],
            'embedding.*' => ['numeric'],
            'matched' => ['sometimes', 'nullable', 'boolean'],
            'distance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'liveFaceImage.regex' => 'Invalid image data URI',
            'embedding.size' => 'Face embedding must have exactly 128 dimensions',
        ];
    }
}
