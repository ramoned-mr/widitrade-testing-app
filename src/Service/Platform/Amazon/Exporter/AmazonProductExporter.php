<?php

namespace App\Service\Platform\Amazon\Exporter;

use App\Service\Platform\Amazon\ValueObject\ExportResult;
use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonExportException;
use App\Service\Platform\Amazon\Processor\AmazonDataProcessorInterface;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Psr\Log\LoggerInterface;

/**
 * Servicio para exportar productos desde la base de datos local hacia formato JSON de Amazon
 * Implementa AmazonProductExporterInterface
 */
class AmazonProductExporter implements AmazonProductExporterInterface
{
    public function __construct(
        private ProductRepository            $productRepository,
        private AmazonDataProcessorInterface $dataProcessor,
        private ?LoggerInterface             $logger = null
    )
    {
    }

    public function exportProducts(string $filePath, bool $onlyActive = true): ExportResult
    {
        try {
            $this->validateExportPath($filePath);

            $this->logger?->info('Iniciando exportación de productos Amazon', [
                'file_path' => $filePath,
                'only_active' => $onlyActive
            ]);

            // Generar contenido JSON
            $jsonContent = $this->generateJsonContent($onlyActive);

            // Escribir archivo
            if (file_put_contents($filePath, $jsonContent, LOCK_EX) === false) {
                throw new AmazonExportException("No se pudo escribir el archivo: $filePath");
            }

            // Obtener estadísticas del resultado
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            $items = $data['SearchResult']['Items'] ?? [];

            $result = new ExportResult(
                totalProcessed: count($items),
                totalExported: count($items),
                skipped: 0,
                failed: 0,
                errors: []
            );

            // Calcular estadísticas adicionales
            $statistics = $this->calculateStatistics($items);
            $result = $result->withStatistics(
                $statistics['with_images'],
                $statistics['with_prices'],
                $statistics['with_rankings']
            )->withFilePath($filePath);

            $this->logger?->info('Exportación completada', [
                'total_exported' => $result->totalExported,
                'file_path' => $filePath,
                'file_size' => filesize($filePath)
            ]);

            return $result;

        } catch (\JsonException $e) {
            $this->logger?->error('Error generando JSON', ['error' => $e->getMessage()]);
            throw new AmazonExportException('Error generando JSON: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger?->error('Error general en exportación', ['error' => $e->getMessage()]);
            throw new AmazonExportException('Error en exportación: ' . $e->getMessage());
        }
    }

    public function generateJsonContent(bool $onlyActive = true): string
    {
        // Obtener productos de la base de datos
        $products = $this->getProductsForExport($onlyActive);

        if (empty($products)) {
            $this->logger?->warning('No se encontraron productos para exportar', ['only_active' => $onlyActive]);
        }

        // Convertir productos a AmazonProductData
        $amazonProductsData = [];
        $errors = [];

        foreach ($products as $product) {
            try {
                $amazonProductData = $this->convertProductToAmazonData($product);
                $amazonProductsData[] = $amazonProductData;
            } catch (\Exception $e) {
                $errors[] = [
                    'asin' => $product->getAsin(),
                    'error' => $e->getMessage()
                ];
                $this->logger?->error('Error convirtiendo producto', [
                    'asin' => $product->getAsin(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Transformar a formato Amazon usando el processor
        $amazonItems = $this->dataProcessor->reverseProcessItems($amazonProductsData);

        // Construir la estructura completa del JSON de Amazon
        $amazonStructure = $this->buildAmazonStructure($amazonItems);

        return json_encode($amazonStructure, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function validateExportPath(string $filePath): bool
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new AmazonExportException("No se pudo crear el directorio: $directory");
            }
        }

        if (!is_writable($directory)) {
            throw new AmazonExportException("El directorio no es escribible: $directory");
        }

        // Verificar que el archivo puede ser creado (si no existe) o sobrescrito (si existe)
        if (file_exists($filePath) && !is_writable($filePath)) {
            throw new AmazonExportException("El archivo no puede ser sobrescrito: $filePath");
        }

        return true;
    }

    /**
     * Obtiene los productos de la base de datos que se van a exportar
     */
    private function getProductsForExport(bool $onlyActive): array
    {
        $criteria = [];

        if ($onlyActive) {
            $criteria['isActive'] = true;
        }

        return $this->productRepository->findBy($criteria, ['id' => 'ASC']);
    }

    /**
     * Convierte una entidad Product a AmazonProductData
     */
    private function convertProductToAmazonData(Product $product): AmazonProductData
    {
        // Convertir imágenes
        $images = [];
        foreach ($product->getImages() as $image) {
            if ($image->getIsActive()) {
                $images[] = [
                    'url' => $image->getUrl(),
                    'width' => $image->getWidth(),
                    'height' => $image->getHeight(),
                    'type' => $image->getType(),
                    'is_primary' => $image->getIsPrimary()
                ];
            }
        }

        // Convertir precios
        $prices = [];
        foreach ($product->getPrices() as $price) {
            if ($price->getIsActive()) {
                $prices[] = [
                    'listing_id' => $price->getListingId(),
                    'amount' => $price->getAmount(),
                    'currency' => $price->getCurrency(),
                    'display_amount' => $price->getDisplayAmount(),
                    'savings_amount' => $price->getSavingsAmount(),
                    'savings_display' => $price->getSavingsDisplay(),
                    'savings_percentage' => $price->getSavingsPercentage(),
                    'is_free_shipping' => $price->getIsFreeShipping(),
                    'violates_map' => $price->getViolatesMap()
                ];
            }
        }

        // Convertir rankings
        $rankings = [];
        foreach ($product->getRankings() as $ranking) {
            if ($ranking->getIsActive()) {
                $rankings[] = [
                    'category_id' => $ranking->getCategoryId(),
                    'category_name' => $ranking->getCategoryName(),
                    'context_free_name' => $ranking->getContextFreeName(),
                    'sales_rank' => $ranking->getSalesRank(),
                    'is_root' => $ranking->getIsRoot()
                ];
            }
        }

        // Obtener datos originales (rawData) que se guardaron durante la importación
        $rawData = $product->getSourceData() ?? [];

        // Si no hay datos originales, crear estructura básica
        if (empty($rawData)) {
            $rawData = $this->createBasicRawData($product);
        }

        return new AmazonProductData(
            asin: $product->getAsin(),
            title: $product->getTitle(),
            brand: $product->getBrand(),
            manufacturer: $product->getManufacturer(),
            detailPageUrl: $product->getAmazonUrl(),
            features: $product->getFeatures(),
            images: $images,
            prices: $prices,
            rankings: $rankings,
            rawData: $rawData
        );
    }

    /**
     * Crea una estructura básica de rawData para productos que no la tienen
     */
    private function createBasicRawData(Product $product): array
    {
        return [
            'ASIN' => $product->getAsin(),
            'DetailPageURL' => $product->getAmazonUrl(),
            'ItemInfo' => [
                'Title' => [
                    'DisplayValue' => $product->getTitle(),
                    'Label' => 'Title',
                    'Locale' => 'es_ES'
                ],
                'ByLineInfo' => [
                    'Brand' => [
                        'DisplayValue' => $product->getBrand(),
                        'Label' => 'Brand',
                        'Locale' => 'es_ES'
                    ],
                    'Manufacturer' => [
                        'DisplayValue' => $product->getManufacturer() ?? $product->getBrand(),
                        'Label' => 'Manufacturer',
                        'Locale' => 'es_ES'
                    ]
                ],
                'Features' => [
                    'DisplayValues' => $product->getFeatures(),
                    'Label' => 'Features',
                    'Locale' => 'es_ES'
                ]
            ],
            'Images' => [
                'Primary' => [
                    'Large' => [
                        'Height' => 500,
                        'URL' => '',
                        'Width' => 500
                    ]
                ]
            ],
            'Offers' => [
                'Listings' => [
                    [
                        'Id' => 'default_listing',
                        'DeliveryInfo' => [
                            'IsFreeShippingEligible' => true
                        ],
                        'Price' => [
                            'Amount' => 0,
                            'Currency' => 'EUR',
                            'DisplayAmount' => '0,00 €',
                            'Savings' => [
                                'Amount' => 0,
                                'Currency' => 'EUR',
                                'DisplayAmount' => '0,00 € (0%)',
                                'Percentage' => 0
                            ]
                        ],
                        'ViolatesMAP' => false
                    ]
                ]
            ],
            'BrowseNodeInfo' => [
                'BrowseNodes' => [
                    [
                        'ContextFreeName' => 'Electrónica',
                        'DisplayName' => 'Electrónica',
                        'Id' => '1000000000',
                        'IsRoot' => false,
                        'SalesRank' => 1
                    ]
                ]
            ]
        ];
    }

    /**
     * Construye la estructura completa del JSON de Amazon
     */
    private function buildAmazonStructure(array $amazonItems): array
    {
        return [
            'SearchResult' => [
                'Items' => $amazonItems,
                'SearchURL' => 'https://www.amazon.es/s?k=productos+exportados',
                'TotalResultCount' => count($amazonItems)
            ]
        ];
    }

    /**
     * Calcula estadísticas adicionales de los items exportados
     */
    private function calculateStatistics(array $items): array
    {
        $withImages = 0;
        $withPrices = 0;
        $withRankings = 0;

        foreach ($items as $item) {
            // Contar productos con imágenes
            if (isset($item['Images']['Primary']['Large']['URL']) && !empty($item['Images']['Primary']['Large']['URL'])) {
                $withImages++;
            }

            // Contar productos con precios
            if (isset($item['Offers']['Listings'][0]['Price']['Amount']) && $item['Offers']['Listings'][0]['Price']['Amount'] > 0) {
                $withPrices++;
            }

            // Contar productos con rankings
            if (isset($item['BrowseNodeInfo']['BrowseNodes'][0]['SalesRank'])) {
                $withRankings++;
            }
        }

        return [
            'with_images' => $withImages,
            'with_prices' => $withPrices,
            'with_rankings' => $withRankings
        ];
    }
}