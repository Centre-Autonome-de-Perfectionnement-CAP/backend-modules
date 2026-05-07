<?php

namespace App\Exceptions;

class FileUploadException extends BusinessException
{
    public function __construct(
        string $fileName = '',
        string $reason = 'Erreur lors du téléchargement du fichier',
        ?\Throwable $previous = null
    ) {
        $message = $fileName 
            ? "Erreur lors du téléchargement du fichier '{$fileName}': {$reason}"
            : $reason;
            
        parent::__construct(
            message: $message,
            errorCode: 'FILE_UPLOAD_ERROR',
            statusCode: 400,
            previous: $previous
        );
    }
}
