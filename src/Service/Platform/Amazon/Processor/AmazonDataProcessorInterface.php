<?php

namespace App\Service\Platform\Amazon\Processor;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;

/**
 * Interface para procesadores de datos de Amazon
 * Orquesta la transformación y validación de datos en ambos sentidos
 * (Importación: JSON → Entidades, Exportación: Entidades → JSON)
 */
interface AmazonDataProcessorInterface
{
    /**
     * Procesa un item crudo de Amazon y devuelve los datos transformados
     * (Transformación: JSON Amazon → ValueObject)
     *
     * @param array $itemData Datos crudos del producto Amazon
     * @return AmazonProductData Datos procesados y validados
     * @throws AmazonDataValidationException Si los datos no son válidos
     */
    public function processItem(array $itemData): AmazonProductData;

    /**
     * Procesa múltiples items de Amazon
     * (Transformación: JSON Amazon → ValueObject)
     *
     * @param array $itemsData Array de datos crudos de productos
     * @return array Array de AmazonProductData
     */
    public function processItems(array $itemsData): array;

    /**
     * Transforma un ProductData de vuelta al formato Amazon
     * (Transformación inversa: ValueObject → JSON Amazon)
     *
     * @param AmazonProductData $productData Datos del producto procesados
     * @return array Datos en formato JSON de Amazon
     * @throws AmazonDataValidationException Si los datos no son válidos
     */
    public function reverseProcessItem(AmazonProductData $productData): array;

    /**
     * Transforma múltiples ProductData de vuelta al formato Amazon
     * (Transformación inversa: ValueObject[] → JSON Amazon)
     *
     * @param array $productsData Array de AmazonProductData
     * @return array Array de datos en formato JSON de Amazon
     */
    public function reverseProcessItems(array $productsData): array;
}