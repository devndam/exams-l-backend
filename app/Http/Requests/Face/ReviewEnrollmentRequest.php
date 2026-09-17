<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;

class ReviewEnrollmentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            'reviewNote' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['status.in' => 'Status must be approved or rejected'];
    }
}
