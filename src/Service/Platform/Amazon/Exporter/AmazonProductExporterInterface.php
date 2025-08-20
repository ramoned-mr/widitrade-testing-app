<?php

namespace App\Service\Platform\Amazon\Exporter;

use App\Service\Platform\Amazon\ValueObject\ExportResult;
use App\Service\Platform\Amazon\Exception\AmazonExportException;

/**
 * Interface para el servicio de exportación de productos de Amazon
 * Define el contrato que deben cumplir todos los exportadores de Amazon
 */
interface AmazonProductExporterInterface
{
    /**
     * Exporta productos desde la base de datos a un archivo JSON de Amazon
     *
     * @param string $filePath Ruta donde se guardará el archivo JSON
     * @param bool $onlyActive Si es true, exporta solo productos activos
     * @return ExportResult Resultado de la operación de exportación
     * @throws AmazonExportException
     */
    public function exportProducts(string $filePath, bool $onlyActive = true): ExportResult;

    /**
     * Genera el contenido JSON completo con estructura de Amazon
     *
     * @param bool $onlyActive Si es true, incluye solo productos activos
     * @return string JSON con estructura completa de Amazon
     * @throws AmazonExportException
     */
    public function generateJsonContent(bool $onlyActive = true): string;

    /**
     * Valida que el archivo puede ser creado en la ruta especificada
     *
     * @param string $filePath Ruta del archivo a validar
     * @return bool True si el archivo puede ser creado
     * @throws AmazonExportException
     */
    public function validateExportPath(string $filePath): bool;
}