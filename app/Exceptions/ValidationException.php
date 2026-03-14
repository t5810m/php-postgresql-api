<?php

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
