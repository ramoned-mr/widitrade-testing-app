<?php

namespace App\Service\Platform\Amazon\ValueObject;

/**
 * Value Object que representa el resultado de una operación de exportación
 */
class ExportResult
{
    public function __construct(
        public readonly int     $totalProcessed = 0,
        public readonly int     $totalExported = 0,
        public readonly int     $skipped = 0,
        public readonly int     $failed = 0,
        public readonly array   $errors = [],
        public readonly ?int    $withImages = null,
        public readonly ?int    $withPrices = null,
        public readonly ?int    $withRankings = null,
        public readonly ?string $filePath = null
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
            $this->totalExported,
            $this->skipped,
            $this->failed + 1,
            $errors,
            $this->withImages,
            $this->withPrices,
            $this->withRankings,
            $this->filePath
        );
    }

    /**
     * Incrementa el contador de procesados
     */
    public function incrementProcessed(): self
    {
        return new self(
            $this->totalProcessed + 1,
            $this->totalExported,
            $this->skipped,
            $this->failed,
            $this->errors,
            $this->withImages,
            $this->withPrices,
            $this->withRankings,
            $this->filePath
        );
    }

    /**
     * Incrementa el contador de exportados
     */
    public function incrementExported(): self
    {
        return new self(
            $this->totalProcessed,
            $this->totalExported + 1,
            $this->skipped,
            $this->failed,
            $this->errors,
            $this->withImages,
            $this->withPrices,
            $this->withRankings,
            $this->filePath
        );
    }

    /**
     * Incrementa el contador de omitidos
     */
    public function incrementSkipped(): self
    {
        return new self(
            $this->totalProcessed,
            $this->totalExported,
            $this->skipped + 1,
            $this->failed,
            $this->errors,
            $this->withImages,
            $this->withPrices,
            $this->withRankings,
            $this->filePath
        );
    }

    /**
     * Establece las estadísticas adicionales
     */
    public function withStatistics(int $withImages, int $withPrices, int $withRankings): self
    {
        return new self(
            $this->totalProcessed,
            $this->totalExported,
            $this->skipped,
            $this->failed,
            $this->errors,
            $withImages,
            $withPrices,
            $withRankings,
            $this->filePath
        );
    }

    /**
     * Establece la ruta del archivo exportado
     */
    public function withFilePath(string $filePath): self
    {
        return new self(
            $this->totalProcessed,
            $this->totalExported,
            $this->skipped,
            $this->failed,
            $this->errors,
            $this->withImages,
            $this->withPrices,
            $this->withRankings,
            $filePath
        );
    }
}