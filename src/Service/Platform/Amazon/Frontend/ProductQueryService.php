<?php

namespace App\Service\Platform\Amazon\Frontend;

use App\Repository\ProductRepository;
use Psr\Log\LoggerInterface;

/**
 * Servicio para consultar productos desde la base de datos
 * Implementa ProductQueryServiceInterface
 */
class ProductQueryService implements ProductQueryServiceInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private ?LoggerInterface  $logger = null
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsByCategory(string $categoryName, ?int $limit = null): array
    {
        try {
            $this->logger?->info('Consultando productos por categoría', [
                'category' => $categoryName,
                'limit' => $limit ?? 'unlimited'
            ]);

            // Consultar productos activos que tengan rankings en la categoría especificada
            $queryBuilder = $this->productRepository->createQueryBuilder('p')
                ->leftJoin('p.rankings', 'r')
                ->leftJoin('p.prices', 'pr')
                ->leftJoin('p.images', 'i')
                ->where('p.isActive = :active')
                ->andWhere('r.isActive = :rankingActive')
                ->andWhere('r.categoryName LIKE :categoryName')
                ->orderBy('r.salesRank', 'ASC')
                ->setParameter('active', true)
                ->setParameter('rankingActive', true)
                ->setParameter('categoryName', '%' . $categoryName . '%');

            // Solo aplicar límite si se especifica
            if ($limit !== null && $limit > 0) {
                $queryBuilder->setMaxResults($limit);
            }

            $products = $queryBuilder->getQuery()->getResult();

            // Filtrar productos que tengan datos completos
            $completeProducts = array_filter($products, [$this, 'hasCompleteData']);

            $this->logger?->info('Productos encontrados', [
                'total_found' => count($products),
                'complete_data' => count($completeProducts),
                'limit_applied' => $limit ?? 'none'
            ]);

            // Si hay límite, aplicarlo después del filtrado
            if ($limit !== null && $limit > 0) {
                return array_slice($completeProducts, 0, $limit);
            }

            return $completeProducts;

        } catch (\Exception $e) {
            $this->logger?->error('Error consultando productos por categoría', [
                'category' => $categoryName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTopRankedProducts(?int $limit = null): array
    {
        try {
            $this->logger?->info('Consultando productos top ranked', [
                'limit' => $limit ?? 'unlimited'
            ]);

            // Consultar productos activos ordenados por mejor ranking
            $queryBuilder = $this->productRepository->createQueryBuilder('p')
                ->leftJoin('p.rankings', 'r')
                ->leftJoin('p.prices', 'pr')
                ->leftJoin('p.images', 'i')
                ->where('p.isActive = :active')
                ->andWhere('r.isActive = :rankingActive')
                ->orderBy('r.salesRank', 'ASC')
                ->addOrderBy('p.createdAt', 'DESC')
                ->setParameter('active', true)
                ->setParameter('rankingActive', true);

            // Obtener más productos para filtrar si hay límite
            if ($limit !== null && $limit > 0) {
                $queryBuilder->setMaxResults($limit * 3); // Buffer para filtrado
            }

            $products = $queryBuilder->getQuery()->getResult();

            // Filtrar productos que tengan datos completos
            $completeProducts = array_filter($products, [$this, 'hasCompleteData']);

            // Eliminar duplicados por ASIN (en caso de múltiples rankings)
            $uniqueProducts = [];
            $seenAsins = [];

            foreach ($completeProducts as $product) {
                if (!in_array($product->getAsin(), $seenAsins)) {
                    $uniqueProducts[] = $product;
                    $seenAsins[] = $product->getAsin();
                }
            }

            $this->logger?->info('Productos procesados', [
                'total_found' => count($products),
                'complete_data' => count($completeProducts),
                'unique_products' => count($uniqueProducts),
                'limit_applied' => $limit ?? 'none'
            ]);

            // Si hay límite, aplicarlo al final
            if ($limit !== null && $limit > 0) {
                return array_slice($uniqueProducts, 0, $limit);
            }

            return $uniqueProducts;

        } catch (\Exception $e) {
            $this->logger?->error('Error consultando productos top ranked', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllProductsByCategory(string $categoryName): array
    {
        return $this->getProductsByCategory($categoryName, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTopRankedProducts(): array
    {
        return $this->getTopRankedProducts(null);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompleteData(object $product): bool
    {
        // Verificar que tenga al menos una imagen activa
        $hasImage = false;
        foreach ($product->getImages() as $image) {
            if ($image->isActive() && !empty($image->getUrl())) {
                $hasImage = true;
                break;
            }
        }

        // Verificar que tenga al menos un precio activo
        $hasPrice = false;
        foreach ($product->getPrices() as $price) {
            if ($price->isActive() && $price->getAmount() > 0) {
                $hasPrice = true;
                break;
            }
        }

        // Verificar que tenga al menos un ranking activo
        $hasRanking = false;
        foreach ($product->getRankings() as $ranking) {
            if ($ranking->isActive()) {
                $hasRanking = true;
                break;
            }
        }

        // Verificar datos básicos (source_data no es requerido)
        $hasBasicData = !empty($product->getTitle())
            && !empty($product->getBrand())
            && !empty($product->getAmazonUrl());

        return $hasImage && $hasPrice && $hasRanking && $hasBasicData;
    }
}