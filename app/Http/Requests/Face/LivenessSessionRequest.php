<?php

namespace App\Http\Requests\Face;

use App\Http\Requests\ApiFormRequest;

class LivenessSessionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'purpose' => ['required', 'in:enrollment,verification'],
        ];
    }

    public function messages(): array
    {
        return ["purpose.in" => "Purpose must be 'enrollment' or 'verification'"];
    }
}
