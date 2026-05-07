<?php

namespace App\Exceptions;

class PeriodOverlapException extends BusinessException
{
    public function __construct(
        string $departmentId,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: "Le département {$departmentId} a déjà une période qui chevauche",
            errorCode: 'PERIOD_OVERLAP',
            statusCode: 422,
            previous: $previous
        );
    }
}
