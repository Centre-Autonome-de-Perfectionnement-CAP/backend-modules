<?php

namespace App\Exceptions;

class ValidationException extends BusinessException
{
    protected $errors;
    
    public function __construct(
        string $message = 'Les données fournies sont invalides',
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'VALIDATION_ERROR',
            statusCode: 422,
            previous: $previous
        );
        $this->errors = $errors;
    }
    
    public function render(): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
        ];
        
        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }
        
        return response()->json($response, $this->getStatusCode());
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
