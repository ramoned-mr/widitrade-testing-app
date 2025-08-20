<?php

namespace App\Service\Platform\Amazon\Exception;

/**
 * Excepción base para errores de exportación de Amazon
 */
class AmazonExportException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Amazon Export Error: $message", $code, $previous);
    }
}