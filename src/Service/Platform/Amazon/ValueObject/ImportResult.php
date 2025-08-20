<?php

namespace App\Service\Platform\Amazon\ValueObject;

/**
 * Value Object que representa el resultado de una operación de importación
 */
class ImportResult
{
    public function __construct(
        public readonly int   $totalProcessed = 0,
        public readonly int   $successfullyImported = 0,
        public readonly int   $updated = 0,
        public readonly int   $skipped = 0,
        public readonly int   $failed = 0,
        public readonly array $errors = []
    )
    {
    }

    /**
     * Añade un error al resultado
     */
    public function addError(string $asin, string $errorMessage): self
    {
        $errors = $this->errors;
        $errors[] = ['asin' => $asin, 'error' => $errorMessage];

        return new self(
            $this->totalProcessed,
            $this->successfullyImported,
            $this->updated,
            $this->skipped,
            $this->failed + 1,
            $errors
        );
    }

    /**
     * Incrementa el contador de procesados
     */
    public function incrementProcessed(): self
    {
        return new self(
            $this->totalProcessed + 1,
            $this->successfullyImported,
            $this->updated,
            $this->skipped,
            $this->failed,
            $this->errors
        );
    }

    /**
     * Incrementa el contador de importados
     */
    public function incrementImported(): self
    {
        return new self(
            $this->totalProcessed,
            $this->successfullyImported + 1,
            $this->updated,
            $this->skipped,
            $this->failed,
            $this->errors
        );
    }

    /**
     * Incrementa el contador de actualizados
     */
    public function incrementUpdated(): self
    {
        return new self(
            $this->totalProcessed,
            $this->successfullyImported,
            $this->updated + 1,
            $this->skipped,
            $this->failed,
            $this->errors
        );
    }

    /**
     * Incrementa el contador de omitidos
     */
    public function incrementSkipped(): self
    {
        return new self(
            $this->totalProcessed,
            $this->successfullyImported,
            $this->updated,
            $this->skipped + 1,
            $this->failed,
            $this->errors
        );
    }
}