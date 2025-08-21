<?php

namespace App\Service\Platform\Amazon\Frontend;

use Psr\Log\LoggerInterface;

/**
 * Servicio para generar puntuaciones y valoraciones aleatorias
 * Implementa ScoreGeneratorServiceInterface
 */
class ScoreGeneratorService implements ScoreGeneratorServiceInterface
{
    private const MIN_SCORE = 9.0;
    private const MAX_SCORE = 9.9;

    private const MIN_STARS = 4.0;
    private const MAX_STARS = 5.0;

    public function __construct(
        private ?LoggerInterface $logger = null
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function generateScore(int $position, object $product): float
    {
        try {
            // Generar puntuación más alta para las primeras posiciones
            $baseScore = self::MAX_SCORE - (($position - 1) * 0.05);

            // Asegurar que no baje del mínimo
            $baseScore = max($baseScore, self::MIN_SCORE);

            // Agregar pequeña variación aleatoria (-0.02 a +0.02)
            $variation = (mt_rand(-20, 20) / 1000);
            $finalScore = $baseScore + $variation;

            // Mantener dentro del rango permitido
            $finalScore = max(self::MIN_SCORE, min(self::MAX_SCORE, $finalScore));

            // Redondear a 1 decimal
            $score = round($finalScore, 1);

            $this->logger?->debug('Puntuación generada', [
                'position' => $position,
                'asin' => $product->getAsin(),
                'base_score' => $baseScore,
                'variation' => $variation,
                'final_score' => $score
            ]);

            return $score;

        } catch (\Exception $e) {
            $this->logger?->error('Error generando puntuación', [
                'position' => $position,
                'error' => $e->getMessage()
            ]);

            // Fallback: puntuación basada solo en posición
            return round(self::MAX_SCORE - (($position - 1) * 0.1), 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateStars(float $score): float
    {
        // Mapear puntuación (9.0-9.9) a estrellas (4.0-5.0)
        $scoreRange = self::MAX_SCORE - self::MIN_SCORE; // 0.9
        $starsRange = self::MAX_STARS - self::MIN_STARS; // 1.0

        $normalizedScore = ($score - self::MIN_SCORE) / $scoreRange; // 0-1
        $stars = self::MIN_STARS + ($normalizedScore * $starsRange);

        // Redondear a medios (.0 o .5)
        $roundedStars = round($stars * 2) / 2;

        return max(self::MIN_STARS, min(self::MAX_STARS, $roundedStars));
    }

    /**
     * {@inheritdoc}
     */
    public function generateQualityLabel(float $score): string
    {
        return match (true) {
            $score >= 9.7 => 'Excepcional',
            $score >= 9.4 => 'Excelente',
            $score >= 9.1 => 'Genial',
            default => 'Bueno'
        };
    }

    /**
     * {@inheritdoc}
     */
    public function generateProductRating(int $position, object $product): array
    {
        $score = $this->generateScore($position, $product);
        $stars = $this->generateStars($score);
        $label = $this->generateQualityLabel($score);

        return [
            'score' => $score,
            'stars' => $stars,
            'label' => $label
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateSpecialBadge(int $position, object $product): ?string
    {
        return match ($position) {
            1 => '#1 MEJOR OPCIÓN 2024',
            3 => '#3 MEJOR VALOR 2024',
            default => null
        };
    }
}