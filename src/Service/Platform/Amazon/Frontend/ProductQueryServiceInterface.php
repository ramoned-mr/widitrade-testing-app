<?php

namespace App\Service\Platform\Amazon\Frontend;

/**
 * Interface para consultar productos desde la base de datos
 * Responsabilidad: Solo lógica de consultas y filtros de BD
 */
interface ProductQueryServiceInterface
{
    /**
     * Obtiene productos activos filtrados por categoría y ordenados por ranking
     *
     * @param string $categoryName Nombre de la categoría (ej: "Barras de sonido")
     * @param int|null $limit Límite de productos a retornar (null = sin límite)
     * @return array Array de productos con sus relaciones cargadas
     */
    public function getProductsByCategory(string $categoryName, ?int $limit = null): array;

    /**
     * Obtiene todos los productos activos ordenados por mejor ranking
     *
     * @param int|null $limit Límite de productos a retornar (null = sin límite)
     * @return array Array de productos ordenados por salesRank ascendente
     */
    public function getTopRankedProducts(?int $limit = null): array;

    /**
     * Obtiene TODOS los productos de una categoría sin límite
     *
     * @param string $categoryName Nombre de la categoría
     * @return array Array completo de productos de la categoría
     */
    public function getAllProductsByCategory(string $categoryName): array;

    /**
     * Obtiene TODOS los productos top ranked sin límite
     *
     * @return array Array completo de productos ordenados por ranking
     */
    public function getAllTopRankedProducts(): array;

    /**
     * Verifica si un producto tiene datos completos para mostrar
     *
     * @param object $product Entidad Product
     * @return bool True si tiene imagen, precio y ranking (source_data no requerido)
     */
    public function hasCompleteData(object $product): bool;
}