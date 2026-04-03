<?php

namespace App\Exceptions;

class ResourceNotFoundException extends BusinessException
{
    public function __construct(
        string $resource = 'Ressource',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: "{$resource} introuvable",
            errorCode: 'RESOURCE_NOT_FOUND',
            statusCode: 404,
            previous: $previous
        );
    }
}
