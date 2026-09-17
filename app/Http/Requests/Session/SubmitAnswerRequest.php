<?php

namespace App\Http\Requests\Session;

use App\Http\Requests\ApiFormRequest;

class SubmitAnswerRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'questionId' => ['required', 'integer', 'min:1'],
            'selectedOption' => ['present', 'nullable', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'questionId.min' => 'Invalid question ID',
        ];
    }
}
