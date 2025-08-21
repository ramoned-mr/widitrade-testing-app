<?php

namespace App\Service\Platform\Amazon\Frontend;

/**
 * Interface para formatear datos de productos para la vista
 * Responsabilidad: Solo formateo y preparación de datos para display
 */
interface ProductFormatterServiceInterface
{
    /**
     * Formatea la información de precio de un producto
     *
     * @param object $product Entidad Product con precios
     * @return array Array con precio formateado, descuentos, etc.
     */
    public function formatPriceInfo(object $product): array;

    /**
     * Formatea las características del producto para mostrar
     *
     * @param array $features Array de características del producto
     * @param int $maxVisible Número máximo de características visibles inicialmente
     * @return array Array con 'visible' y 'hidden' features
     */
    public function formatFeatures(array $features, int $maxVisible = 3): array;

    /**
     * Obtiene la imagen principal del producto
     *
     * @param object $product Entidad Product con imágenes
     * @return array Array con información de la imagen principal
     */
    public function getPrimaryImage(object $product): array;

    /**
     * Formatea el título del producto para display
     *
     * @param string $title Título original del producto
     * @param int $maxLength Longitud máxima permitida
     * @return string Título formateado
     */
    public function formatTitle(string $title, int $maxLength = 80): string;

    /**
     * Formatea toda la información del producto para la vista
     *
     * @param object $product Entidad Product
     * @param int $position Posición en el ranking
     * @param array $rating Datos de valoración generados
     * @return array Array completo con todos los datos formateados
     */
    public function formatProductForDisplay(object $product, int $position, array $rating): array;

    /**
     * Genera el URL de Amazon con tracking
     *
     * @param string $amazonUrl URL original de Amazon
     * @return string URL con parámetros de tracking
     */
    public function formatAmazonUrl(string $amazonUrl): string;
}