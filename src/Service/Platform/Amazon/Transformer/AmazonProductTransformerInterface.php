<?php

namespace App\Service\Platform\Amazon\Transformer;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;

/**
 * Interface para transformadores de datos de Amazon
 * Convierte datos crudos de Amazon en Value Objects validados
 */
interface AmazonProductTransformerInterface
{
    /**
     * Transforma datos crudos de un producto Amazon en un Value Object validado
     *
     * @param array $itemData Datos crudos del producto Amazon
     * @return AmazonProductData Value Object con datos validados
     * @throws AmazonDataValidationException Si los datos no son válidos
     */
    public function transformProductData(array $itemData): AmazonProductData;

    /**
     * Valida la estructura básica de los datos de un producto Amazon
     *
     * @param array $itemData Datos a validar
     * @return bool True si la estructura es válida
     * @throws AmazonDataValidationException Si la validación falla
     */
    public function validateProductData(array $itemData): bool;
}