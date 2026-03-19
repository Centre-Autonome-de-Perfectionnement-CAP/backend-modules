<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessException extends Exception
{
    protected $statusCode;
    protected $errorCode;

    public function __construct(
        string $message = "Une erreur métier s'est produite",
        string $errorCode = 'BUSINESS_ERROR',
        int $statusCode = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->statusCode);
    }

    public function getstatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
