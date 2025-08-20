<?php

namespace App\Service\Platform\Amazon\ValueObject;

/**
 * Value Object que representa los datos de un producto de Amazon
 * Inmutable y validado
 */
class AmazonProductData
{
    public function __construct(
        public readonly string  $asin,
        public readonly string  $title,
        public readonly string  $brand,
        public readonly ?string $manufacturer,
        public readonly string  $detailPageUrl,
        public readonly array   $features,
        public readonly array   $images,
        public readonly array   $prices,
        public readonly array   $rankings,
        public readonly array   $rawData
    )
    {
        $this->validate();
    }

    /**
     * Valida la integridad de los datos del producto
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->asin)) {
            throw new \InvalidArgumentException('ASIN no puede estar vacío');
        }

        if (empty($this->title)) {
            throw new \InvalidArgumentException('Title no puede estar vacío');
        }

        if (empty($this->brand)) {
            throw new \InvalidArgumentException('Brand no puede estar vacío');
        }

        if (empty($this->detailPageUrl)) {
            throw new \InvalidArgumentException('DetailPageUrl no puede estar vacío');
        }
    }
}