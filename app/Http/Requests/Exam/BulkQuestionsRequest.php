<?php

namespace App\Http\Requests\Exam;

use App\Http\Requests\ApiFormRequest;

class BulkQuestionsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.questionText' => ['required', 'string', 'min:1'],
            'questions.*.optionA' => ['required', 'string', 'min:1'],
            'questions.*.optionB' => ['required', 'string', 'min:1'],
            'questions.*.optionC' => ['required', 'string', 'min:1'],
            'questions.*.optionD' => ['required', 'string', 'min:1'],
            'questions.*.correctOption' => ['required', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.min' => 'At least one question is required',
            'questions.*.correctOption.in' => 'Correct option must be A, B, C, or D',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function validated($key = null, $default = null): array
    {
        return array_map(fn (array $q) => [
            'question_text' => $q['questionText'],
            'option_a' => $q['optionA'],
            'option_b' => $q['optionB'],
            'option_c' => $q['optionC'],
            'option_d' => $q['optionD'],
            'correct_option' => $q['correctOption'],
        ], $this->input('questions'));
    }
}
