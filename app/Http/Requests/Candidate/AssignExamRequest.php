<?php

namespace App\Http\Requests\Candidate;

use App\Http\Requests\ApiFormRequest;

class AssignExamRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'candidateId' => ['required', 'integer', 'min:1'],
            'examTypeIds' => ['required', 'array', 'min:1'],
            'examTypeIds.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'candidateId.min' => 'Invalid candidate ID',
            'examTypeIds.min' => 'At least one exam type is required',
            'examTypeIds.*.min' => 'Invalid exam type ID',
        ];
    }
}
