<?php

namespace App\Service\Platform\Amazon\Processor;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;

/**
 * Interface para procesadores de datos de Amazon
 * Orquesta la transformación y validación de datos
 */
interface AmazonDataProcessorInterface
{
    /**
     * Procesa un item crudo de Amazon y devuelve los datos transformados
     *
     * @param array $itemData Datos crudos del producto Amazon
     * @return AmazonProductData Datos procesados y validados
     * @throws AmazonDataValidationException Si los datos no son válidos
     */
    public function processItem(array $itemData): AmazonProductData;

    /**
     * Procesa múltiples items de Amazon
     *
     * @param array $itemsData Array de datos crudos de productos
     * @return array Array de AmazonProductData
     */
    public function processItems(array $itemsData): array;
}