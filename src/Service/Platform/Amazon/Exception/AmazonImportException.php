<?php

namespace App\Service\Platform\Amazon\Exception;

/**
 * Excepción base para errores de importación de Amazon
 */
class AmazonImportException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Amazon Import Error: $message", $code, $previous);
    }
}