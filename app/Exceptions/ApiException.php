<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    /** @var array<int, array{field?: string, message: string}> */
    public array $errors;

    public int $statusCode;

    public function __construct(int $statusCode, string $message, array $errors = [])
    {
        parent::__construct($message);

        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }
}
