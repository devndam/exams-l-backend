<?php

namespace App\Http\Requests\Exam;

use App\Http\Requests\ApiFormRequest;

class UpdateExamTypeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:1', 'max:200'],
            'totalQuestions' => ['sometimes', 'integer', 'min:1'],
            'timePerQuestion' => ['sometimes', 'integer', 'min:1'],
            'requiresFaceVerification' => ['sometimes', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $map = [
            'name' => 'name',
            'totalQuestions' => 'total_questions',
            'timePerQuestion' => 'time_per_question',
            'requiresFaceVerification' => 'requires_face_verification',
        ];

        $data = [];
        foreach ($map as $input => $column) {
            if ($this->has($input)) {
                $data[$column] = $input === 'requiresFaceVerification' ? $this->boolean($input) : $this->input($input);
            }
        }

        return $data;
    }
}
