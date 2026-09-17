<?php

namespace App\Http\Requests\Candidate;

use App\Http\Requests\ApiFormRequest;

class UpdateCertificateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'certificateName' => ['required', 'string', 'min:1', 'max:200'],
            'certificateEmail' => ['required', 'email'],
            'certificatePhone' => ['required', 'string', 'min:1', 'max:30'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return [
            'certificate_name' => $this->input('certificateName'),
            'certificate_email' => $this->input('certificateEmail'),
            'certificate_phone' => $this->input('certificatePhone'),
        ];
    }
}
