<?php

namespace App\Service\Platform\Amazon\Transformer;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;

/**
 * Interface para transformadores de datos de Amazon
 * Convierte datos crudos de Amazon en Value Objects validados y viceversa
 */
interface AmazonProductTransformerInterface
{
    /**
     * Transforma datos crudos de un producto Amazon en un Value Object validado
     * (Transformación: JSON Amazon → ValueObject)
     *
     * @param array $itemData Datos crudos del producto Amazon
     * @return AmazonProductData Value Object con datos validados
     * @throws AmazonDataValidationException Si los datos no son válidos
     */
    public function transformProductData(array $itemData): AmazonProductData;

    /**
     * Valida la estructura básica de los datos de un producto Amazon
     * (Validación: JSON Amazon → ValueObject)
     *
     * @param array $itemData Datos a validar
     * @return bool True si la estructura es válida
     * @throws AmazonDataValidationException Si la validación falla
     */
    public function validateProductData(array $itemData): bool;

    /**
     * Transforma un Value Object de producto de vuelta al formato Amazon
     * (Transformación inversa: ValueObject → JSON Amazon)
     *
     * @param AmazonProductData $productData Datos del producto procesados
     * @return array Datos en formato JSON de Amazon
     * @throws AmazonDataValidationException Si los datos no son válidos
     */
    public function reverseTransformProductData(AmazonProductData $productData): array;

    /**
     * Valida la estructura básica de los datos de un producto para exportación
     * (Validación: ValueObject → JSON Amazon)
     *
     * @param AmazonProductData $productData Datos a validar
     * @return bool True si la estructura es válida
     * @throws AmazonDataValidationException Si la validación falla
     */
    public function validateProductDataForExport(AmazonProductData $productData): bool;
}