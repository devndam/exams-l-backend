<?php

namespace App\Http\Requests\Session;

use App\Http\Requests\ApiFormRequest;

class CancelSessionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:logout,tab_switch'],
        ];
    }
}
