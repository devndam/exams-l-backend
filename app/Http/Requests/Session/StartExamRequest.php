<?php

namespace App\Http\Requests\Session;

use App\Http\Requests\ApiFormRequest;

class StartExamRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'liveFaceImage' => ['sometimes', 'nullable', 'regex:/^data:image\/(png|jpeg|jpg|webp);base64,/'],
        ];
    }

    public function messages(): array
    {
        return ['liveFaceImage.regex' => 'Invalid image data URI'];
    }
}
