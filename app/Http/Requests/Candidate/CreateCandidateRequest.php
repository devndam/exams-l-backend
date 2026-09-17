<?php

namespace App\Http\Requests\Candidate;

use App\Http\Requests\ApiFormRequest;

class CreateCandidateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'candidateId' => ['required', 'string', 'min:1'],
            'fullName' => ['required', 'string', 'min:1'],
            'email' => ['required', 'email'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return [
            'candidate_id' => $this->input('candidateId'),
            'full_name' => $this->input('fullName'),
            'email' => $this->input('email'),
        ];
    }
}
