<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;

class EnrollFaceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'string', 'min:100', 'starts_with:data:image/'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.min' => 'Image data is required',
            'image.starts_with' => 'Invalid image data URI format',
        ];
    }
}
