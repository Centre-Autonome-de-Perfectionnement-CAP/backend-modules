<?php

namespace App\Exceptions;

class DatabaseException extends BusinessException
{
    public function __construct(
        string $message = 'Erreur lors de l\'opération sur la base de données',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'DATABASE_ERROR',
            statusCode: 500,
            previous: $previous
        );
    }
}
