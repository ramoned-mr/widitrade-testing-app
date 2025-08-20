<?php

namespace App\Service\Platform\Amazon\Exception;

/**
 * Excepción para errores de validación de datos de Amazon
 */
class AmazonDataValidationException extends AmazonImportException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Validation Error: $message", $code, $previous);
    }
}