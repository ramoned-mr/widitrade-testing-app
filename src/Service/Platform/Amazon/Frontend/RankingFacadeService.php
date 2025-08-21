<?php

namespace App\Service\Platform\Amazon\Frontend;

use Psr\Log\LoggerInterface;

/**
 * Servicio Facade que orquesta todo el proceso de ranking
 * Implementa RankingFacadeServiceInterface
 */
class RankingFacadeService implements RankingFacadeServiceInterface
{
    private array $stats = [
        'products_queried' => 0,
        'products_processed' => 0,
        'products_with_complete_data' => 0,
        'processing_time' => 0
    ];

    public function __construct(
        private ProductQueryServiceInterface     $productQueryService,
        private ScoreGeneratorServiceInterface   $scoreGeneratorService,
        private ProductFormatterServiceInterface $productFormatterService,
        private ?LoggerInterface                 $logger = null
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTopProductsForDisplay(?string $category = null, ?int $limit = null): array
    {
        $startTime = microtime(true);

        try {
            $this->logger?->info('Iniciando generación de ranking', [
                'category' => $category,
                'limit' => $limit ?? 'unlimited'
            ]);

            // 1. Consultar productos desde la base de datos
            $products = $category
                ? $this->productQueryService->getProductsByCategory($category, $limit)
                : $this->productQueryService->getTopRankedProducts($limit);

            $this->stats['products_queried'] = count($products);

            if (empty($products)) {
                $this->logger?->warning('No se encontraron productos', ['category' => $category]);
                return [];
            }

            // 2. Procesar cada producto
            $formattedProducts = [];
            $position = 1;

            foreach ($products as $product) {
                try {
                    // 3. Generar puntuación y valoración
                    $rating = $this->scoreGeneratorService->generateProductRating($position, $product);

                    // 4. Agregar badge especial si aplica
                    $specialBadge = $this->scoreGeneratorService->generateSpecialBadge($position, $product);
                    if ($specialBadge) {
                        $rating['special_badge'] = $specialBadge;
                    }

                    // 5. Formatear toda la información del producto
                    $formattedProduct = $this->productFormatterService->formatProductForDisplay(
                        $product,
                        $position,
                        $rating
                    );

                    $formattedProducts[] = $formattedProduct;
                    $position++;

                    $this->stats['products_processed']++;

                } catch (\Exception $e) {
                    $this->logger?->error('Error procesando producto', [
                        'asin' => $product->getAsin(),
                        'position' => $position,
                        'error' => $e->getMessage()
                    ]);
                    // Continuar con el siguiente producto
                    continue;
                }
            }

            $this->stats['products_with_complete_data'] = count($formattedProducts);
            $this->stats['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger?->info('Ranking generado exitosamente', [
                'stats' => $this->stats,
                'final_count' => count($formattedProducts)
            ]);

            return $formattedProducts;

        } catch (\Exception $e) {
            $this->logger?->error('Error generando ranking', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);

            $this->stats['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllProductsForDisplay(?string $category = null): array
    {
        return $this->getTopProductsForDisplay($category, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getSoundbarRanking(?int $limit = null): array
    {
        return $this->getTopProductsForDisplay('Barras de sonido', $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSoundbarProducts(): array
    {
        return $this->getSoundbarRanking(null);
    }

    /**
     * {@inheritdoc}
     */
    public function getRankingStats(): array
    {
        return $this->stats;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAvailableProducts(?string $category = null): bool
    {
        try {
            $products = $category
                ? $this->productQueryService->getProductsByCategory($category, 1)
                : $this->productQueryService->getTopRankedProducts(1);

            return !empty($products);

        } catch (\Exception $e) {
            $this->logger?->error('Error verificando productos disponibles', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductCount(?string $category = null): int
    {
        try {
            $products = $category
                ? $this->productQueryService->getAllProductsByCategory($category)
                : $this->productQueryService->getAllTopRankedProducts();

            return count($products);

        } catch (\Exception $e) {
            $this->logger?->error('Error contando productos', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}