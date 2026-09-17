<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class AdminLoginRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Invalid email address',
            'password.min' => 'Password must be at least 6 characters',
        ];
    }
}
