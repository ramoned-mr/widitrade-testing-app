<?php

namespace App\Service\Platform\Amazon\Frontend;

/**
 * Interface para el servicio Facade que orquesta todo el proceso de ranking
 * Responsabilidad: Coordinar los servicios especializados y proveer API simple al controlador
 */
interface RankingFacadeServiceInterface
{
    /**
     * Obtiene el top de productos con toda la información formateada para la vista
     *
     * @param string|null $category Categoría específica (null para todas)
     * @param int|null $limit Número de productos a retornar (null = sin límite)
     * @return array Array de productos completamente formateados para display
     */
    public function getTopProductsForDisplay(?string $category = null, ?int $limit = null): array;

    /**
     * Obtiene TODOS los productos disponibles sin límite
     *
     * @param string|null $category Categoría específica (null para todas)
     * @return array Array completo de productos formateados
     */
    public function getAllProductsForDisplay(?string $category = null): array;

    /**
     * Obtiene productos de barras de sonido específicamente
     *
     * @param int|null $limit Número de productos a retornar (null = sin límite)
     * @return array Array de productos de barras de sonido formateados
     */
    public function getSoundbarRanking(?int $limit = null): array;

    /**
     * Obtiene TODAS las barras de sonido disponibles
     *
     * @return array Array completo de barras de sonido formateadas
     */
    public function getAllSoundbarProducts(): array;

    /**
     * Obtiene estadísticas del ranking generado
     *
     * @return array Array con estadísticas del proceso
     */
    public function getRankingStats(): array;

    /**
     * Verifica si hay productos disponibles para mostrar
     *
     * @param string|null $category Categoría a verificar
     * @return bool True si hay productos disponibles
     */
    public function hasAvailableProducts(?string $category = null): bool;

    /**
     * Obtiene el número total de productos disponibles
     *
     * @param string|null $category Categoría a contar (null para todas)
     * @return int Número total de productos
     */
    public function getProductCount(?string $category = null): int;
}