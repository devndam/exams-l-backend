<?php

namespace App\Http\Requests\Result;

use App\Http\Requests\ApiFormRequest;

class ResultQueryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'examTypeId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'candidateId' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
