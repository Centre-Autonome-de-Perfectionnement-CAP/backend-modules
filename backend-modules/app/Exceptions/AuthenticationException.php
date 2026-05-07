<?php

namespace App\Exceptions;

class AuthenticationException extends BusinessException
{
    public function __construct(
        string $message = 'Authentification requise',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'AUTHENTICATION_REQUIRED',
            statusCode: 401,
            previous: $previous
        );
    }
}
