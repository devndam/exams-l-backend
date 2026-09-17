<?php

namespace App\Http\Requests\Exam;

use App\Http\Requests\ApiFormRequest;

class CreateExamTypeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:200'],
            'totalQuestions' => ['required', 'integer', 'min:1'],
            'timePerQuestion' => ['required', 'integer', 'min:1'],
            'requiresFaceVerification' => ['sometimes', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return [
            'name' => $this->input('name'),
            'total_questions' => $this->input('totalQuestions'),
            'time_per_question' => $this->input('timePerQuestion'),
            'requires_face_verification' => $this->boolean('requiresFaceVerification', true),
        ];
    }
}
