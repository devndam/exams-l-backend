<?php

namespace App\Http\Requests\Exam;

use App\Http\Requests\ApiFormRequest;

class CreateQuestionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'questionText' => ['required', 'string', 'min:1'],
            'optionA' => ['required', 'string', 'min:1'],
            'optionB' => ['required', 'string', 'min:1'],
            'optionC' => ['required', 'string', 'min:1'],
            'optionD' => ['required', 'string', 'min:1'],
            'correctOption' => ['required', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return ['correctOption.in' => 'Correct option must be A, B, C, or D'];
    }

    public function validated($key = null, $default = null): array
    {
        return [
            'question_text' => $this->input('questionText'),
            'option_a' => $this->input('optionA'),
            'option_b' => $this->input('optionB'),
            'option_c' => $this->input('optionC'),
            'option_d' => $this->input('optionD'),
            'correct_option' => $this->input('correctOption'),
        ];
    }
}
