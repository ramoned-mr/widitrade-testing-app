<?php

namespace App\Service\Admin\Product;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\ProductPrice;
use App\Entity\ProductRanking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Servicio para procesar collections de productos desde formularios
 * Maneja la conversión de datos de request a entidades relacionadas
 */
class ProductCollectionProcessor implements ProductCollectionProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processAllCollections(Request $request, Product $product): void
    {
        $this->processImagesFromRequest($request, $product);
        $this->processPricesFromRequest($request, $product);
        $this->processRankingsFromRequest($request, $product);
        $this->processFeaturesFromRequest($request, $product);
    }

    /**
     * {@inheritdoc}
     */
    public function processImagesFromRequest(Request $request, Product $product): void
    {
        $imagesData = $request->request->all('images') ?? [];
        $primaryImageIndex = $request->request->get('primaryImageIndex');

        // Limpiar imágenes existentes
        foreach ($product->getImages() as $image) {
            $this->entityManager->remove($image);
        }

        foreach ($imagesData as $index => $imageData) {
            if (empty($imageData['url'])) continue;

            $image = new ProductImage();
            $image->setUrl($imageData['url'])
                ->setWidth((int)($imageData['width'] ?? 500))
                ->setHeight((int)($imageData['height'] ?? 500))
                ->setType($imageData['type'] ?? 'large')
                ->setOrderPosition((int)($imageData['orderPosition'] ?? 0))
                ->setAltText($imageData['altText'] ?? null)
                ->setIsPrimary($primaryImageIndex !== null && $primaryImageIndex == $index)
                ->setProduct($product);

            $this->entityManager->persist($image);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPricesFromRequest(Request $request, Product $product): void
    {
        $pricesData = $request->request->all('prices') ?? [];

        // Limpiar precios existentes
        foreach ($product->getPrices() as $price) {
            $this->entityManager->remove($price);
        }

        foreach ($pricesData as $priceData) {
            if (empty($priceData['listingId']) || empty($priceData['amount'])) continue;

            $price = new ProductPrice();
            $price->setListingId($priceData['listingId'])
                ->setAmount($priceData['amount'])
                ->setCurrency($priceData['currency'] ?? 'EUR')
                ->setDisplayAmount($priceData['displayAmount'] ?? '')
                ->setSavingsAmount($priceData['savingsAmount'] ?? null)
                ->setSavingsDisplay($priceData['savingsDisplay'] ?? null)
                ->setSavingsPercentage($priceData['savingsPercentage'] ?? null)
                ->setIsFreeShipping(!empty($priceData['isFreeShipping']))
                ->setViolatesMap(false)
                ->setProduct($product);

            $this->entityManager->persist($price);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processRankingsFromRequest(Request $request, Product $product): void
    {
        $rankingsData = $request->request->all('rankings') ?? [];

        // Limpiar rankings existentes
        foreach ($product->getRankings() as $ranking) {
            $this->entityManager->remove($ranking);
        }

        foreach ($rankingsData as $rankingData) {
            if (empty($rankingData['categoryId']) || empty($rankingData['salesRank'])) continue;

            $ranking = new ProductRanking();
            $ranking->setCategoryId($rankingData['categoryId'])
                ->setCategoryName($rankingData['categoryName'] ?? '')
                ->setContextFreeName($rankingData['contextFreeName'] ?? null)
                ->setSalesRank((int)$rankingData['salesRank'])
                ->setIsRoot(!empty($rankingData['isRoot']))
                ->setProduct($product);

            $this->entityManager->persist($ranking);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processFeaturesFromRequest(Request $request, Product $product): void
    {
        $featuresData = $request->request->all('features') ?? [];

        // Filtrar features vacías
        $features = array_filter($featuresData, function ($feature) {
            return !empty(trim($feature));
        });

        $product->setFeatures(array_values($features));
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryImage(Product $product): ?ProductImage
    {
        foreach ($product->getImages() as $image) {
            if ($image->getIsPrimary()) {
                return $image;
            }
        }

        return $product->getImages()->first() ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstPrice(Product $product): ?ProductPrice
    {
        return $product->getPrices()->first() ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestRanking(Product $product): ?ProductRanking
    {
        $bestRanking = null;
        $bestRank = PHP_INT_MAX;

        foreach ($product->getRankings() as $ranking) {
            if ($ranking->getSalesRank() < $bestRank) {
                $bestRank = $ranking->getSalesRank();
                $bestRanking = $ranking;
            }
        }

        return $bestRanking;
    }
}