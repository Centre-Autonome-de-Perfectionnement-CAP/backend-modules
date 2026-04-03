<?php

namespace App\Exceptions;

class UnauthorizedException extends BusinessException
{
    public function __construct(
        string $message = 'Non autorisé',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'UNAUTHORIZED',
            statusCode: 403,
            previous: $previous
        );
    }
}
