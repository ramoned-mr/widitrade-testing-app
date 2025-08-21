<?php

namespace App\Service\Platform\Amazon\Frontend;

/**
 * Interface para generar puntuaciones y valoraciones aleatorias
 * Responsabilidad: Solo lógica de generación de scores y etiquetas
 */
interface ScoreGeneratorServiceInterface
{
    /**
     * Genera una puntuación aleatoria para un producto
     *
     * @param int $position Posición en el ranking (1-10)
     * @param object $product Entidad Product para contexto
     * @return float Puntuación entre 9.0 y 9.9
     */
    public function generateScore(int $position, object $product): float;

    /**
     * Genera el número de estrellas basado en la puntuación
     *
     * @param float $score Puntuación generada
     * @return float Número de estrellas (4.0-5.0)
     */
    public function generateStars(float $score): float;

    /**
     * Genera la etiqueta de calidad basada en la puntuación
     *
     * @param float $score Puntuación generada
     * @return string Etiqueta ("Excepcional", "Excelente", "Genial")
     */
    public function generateQualityLabel(float $score): string;

    /**
     * Genera datos completos de valoración para un producto
     *
     * @param int $position Posición en el ranking
     * @param object $product Entidad Product
     * @return array Array con 'score', 'stars', 'label'
     */
    public function generateProductRating(int $position, object $product): array;

    /**
     * Genera etiquetas especiales para productos destacados
     *
     * @param int $position Posición en el ranking
     * @param object $product Entidad Product
     * @return string|null Etiqueta especial ("MEJOR OPCIÓN 2024", "MEJOR VALOR 2024")
     */
    public function generateSpecialBadge(int $position, object $product): ?string;
}