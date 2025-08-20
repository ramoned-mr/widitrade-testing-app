<?php

namespace App\Service\Platform\Amazon\Importer;

use App\Service\Platform\Amazon\ValueObject\ImportResult;
use App\Service\Platform\Amazon\Exception\AmazonImportException;

/**
 * Interface para el servicio de importación de productos de Amazon
 * Define el contrato que deben cumplir todos los importadores de Amazon
 */
interface AmazonProductImporterInterface
{
    /**
     * Importa productos desde un JSON de Amazon
     *
     * @param string $jsonData JSON con los datos de productos de Amazon
     * @param bool $forceUpdate Si es true, actualiza productos existentes
     * @param int|null $limit Límite de productos a importar (null para todos)
     * @return ImportResult Resultado de la operación de importación
     * @throws AmazonImportException
     */
    public function importProducts(string $jsonData, bool $forceUpdate = false, ?int $limit = null): ImportResult;

    /**
     * Valida la estructura del JSON de Amazon
     *
     * @param string $jsonData JSON a validar
     * @return bool True si el JSON es válido
     * @throws AmazonImportException
     */
    public function validateJson(string $jsonData): bool;
}