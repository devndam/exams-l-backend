<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class CandidateLoginRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'candidateId' => ['required', 'string', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['candidateId.required' => 'Candidate ID is required'];
    }
}
