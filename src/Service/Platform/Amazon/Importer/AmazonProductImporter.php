<?php

namespace App\Service\Platform\Amazon\Importer;

use App\Service\Platform\Amazon\ValueObject\ImportResult;
use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonImportException;
use App\Service\Platform\Amazon\Processor\AmazonDataProcessorInterface;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\ProductPrice;
use App\Entity\ProductRanking;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para importar productos desde Amazon a la base de datos local
 * Implementa AmazonProductImporterInterface
 */
class AmazonProductImporter implements AmazonProductImporterInterface
{
    public function __construct(
        private EntityManagerInterface       $entityManager,
        private ProductRepository            $productRepository,
        private AmazonDataProcessorInterface $dataProcessor,
        private ?LoggerInterface             $logger = null
    )
    {
    }

    public function importProducts(string $jsonData, bool $forceUpdate = true, ?int $limit = null): ImportResult
    {
        try {
            $this->validateJson($jsonData);
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            $result = new ImportResult();
            $items = $data['SearchResult']['Items'] ?? [];

            if ($limit !== null) {
                $items = array_slice($items, 0, $limit);
            }

            $this->logger?->info('Iniciando importación de productos Amazon', [
                'total_items' => count($items),
                'force_update' => $forceUpdate,
                'limit' => $limit
            ]);

            foreach ($items as $item) {
                $result = $result->incrementProcessed();
                $asin = $item['ASIN'] ?? 'unknown';

                try {
                    $productData = $this->dataProcessor->processItem($item);
                    $this->processProduct($productData, $forceUpdate);

                    $result = $result->incrementImported();
                    $this->logger?->debug('Producto importado exitosamente', ['asin' => $asin]);

                } catch (\Exception $e) {
                    $result = $result->addError($asin, $e->getMessage());
                    $this->logger?->error('Error importando producto', [
                        'asin' => $asin,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->entityManager->flush();

            $this->logger?->info('Importación completada', [
                'total_processed' => $result->totalProcessed,
                'imported' => $result->successfullyImported,
                'errors' => $result->failed
            ]);

            return $result;

        } catch (\JsonException $e) {
            $this->logger?->error('JSON inválido en importación', ['error' => $e->getMessage()]);
            throw new AmazonImportException('JSON inválido: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger?->error('Error general en importación', ['error' => $e->getMessage()]);
            throw new AmazonImportException('Error en importación: ' . $e->getMessage());
        }
    }

    public function validateJson(string $jsonData): bool
    {
        try {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['SearchResult']['Items'])) {
                throw new AmazonImportException('Estructura JSON inválida: falta SearchResult.Items');
            }

            if (!is_array($data['SearchResult']['Items'])) {
                throw new AmazonImportException('Items debe ser un array');
            }

            return true;

        } catch (\JsonException $e) {
            throw new AmazonImportException('JSON inválido: ' . $e->getMessage());
        }
    }

    private function processProduct(AmazonProductData $productData, bool $forceUpdate): void
    {
        $product = $this->productRepository->findOneBy(['asin' => $productData->asin]);

        if ($product && !$forceUpdate) {
            return; // Producto existe y no forzamos actualización
        }

        if (!$product) {
            // Crear nuevo producto
            $product = new Product();
            $product->setAsin($productData->asin);
            $this->entityManager->persist($product);
        }

        // Actualizar datos del producto
        $product
            ->setTitle($productData->title)
            ->setBrand($productData->brand)
            ->setManufacturer($productData->manufacturer)
            ->setAmazonUrl($productData->detailPageUrl)
            ->setFeatures($productData->features)
            ->setSourceData($productData->rawData);

        // Generar slug automáticamente si está vacío
        if (!$product->getSlug()) {
            $slug = $this->generateSlugFromTitle($productData->title);
            $product->setSlug($slug);
        }

        // Procesar imágenes
        $this->processImages($product, $productData->images);

        // Procesar precios
        $this->processPrices($product, $productData->prices);

        // Procesar rankings
        $this->processRankings($product, $productData->rankings);
    }

    /**
     * Genera un slug a partir del título del producto
     */
    private function generateSlugFromTitle(string $title): string
    {
        // Convertir a minúsculas
        $slug = mb_strtolower($title, 'UTF-8');

        // Reemplazar caracteres especiales y espacios
        $slug = preg_replace('/[^a-z0-9áéíóúüñ]+/u', '-', $slug);

        // Eliminar guiones múltiples y guiones al inicio/final
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Limitar longitud a 255 caracteres
        return substr($slug, 0, 255);
    }

    private function processImages(Product $product, array $imagesData): void
    {
        // Limpiar imágenes existentes
        foreach ($product->getImages() as $image) {
            $this->entityManager->remove($image);
        }

        // Agregar nuevas imágenes
        foreach ($imagesData as $imageData) {
            $image = new ProductImage();
            $image
                ->setUrl($imageData['url'])
                ->setWidth($imageData['width'])
                ->setHeight($imageData['height'])
                ->setType($imageData['type'])
                ->setIsPrimary($imageData['is_primary'])
                ->setProduct($product);

            $this->entityManager->persist($image);
        }
    }

    private function processPrices(Product $product, array $pricesData): void
    {
        // Limpiar precios existentes
        foreach ($product->getPrices() as $price) {
            $this->entityManager->remove($price);
        }

        // Agregar nuevos precios
        foreach ($pricesData as $priceData) {
            $price = new ProductPrice();
            $price
                ->setListingId($priceData['listing_id'])
                ->setAmount($priceData['amount'])
                ->setCurrency($priceData['currency'])
                ->setDisplayAmount($priceData['display_amount'])
                ->setSavingsAmount($priceData['savings_amount'])
                ->setSavingsDisplay($priceData['savings_display'])
                ->setSavingsPercentage($priceData['savings_percentage'])
                ->setIsFreeShipping($priceData['is_free_shipping'])
                ->setViolatesMap($priceData['violates_map'])
                ->setProduct($product);

            $this->entityManager->persist($price);
        }
    }

    private function processRankings(Product $product, array $rankingsData): void
    {
        // Limpiar rankings existentes
        foreach ($product->getRankings() as $ranking) {
            $this->entityManager->remove($ranking);
        }

        // Agregar nuevos rankings
        foreach ($rankingsData as $rankingData) {
            $ranking = new ProductRanking();
            $ranking
                ->setCategoryId($rankingData['category_id'])
                ->setCategoryName($rankingData['category_name'])
                ->setContextFreeName($rankingData['context_free_name'])
                ->setSalesRank($rankingData['sales_rank'])
                ->setIsRoot($rankingData['is_root'])
                ->setProduct($product);

            $this->entityManager->persist($ranking);
        }
    }
}