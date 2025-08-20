<?php

namespace App\Service\Platform\Amazon\Transformer;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;
use Psr\Log\LoggerInterface;

/**
 * Implementación del transformador de datos de Amazon
 * Maneja la transformación bidireccional de datos
 */
class AmazonProductTransformer implements AmazonProductTransformerInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function transformProductData(array $itemData): AmazonProductData
    {
        $this->validateProductData($itemData);

        try {
            return new AmazonProductData(
                asin: $itemData['ASIN'],
                title: $itemData['ItemInfo']['Title']['DisplayValue'],
                brand: $itemData['ItemInfo']['ByLineInfo']['Brand']['DisplayValue'],
                manufacturer: $itemData['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'] ?? null,
                detailPageUrl: $itemData['DetailPageURL'],
                features: $itemData['ItemInfo']['Features']['DisplayValues'] ?? [],
                images: $this->extractImages($itemData),
                prices: $this->extractPrices($itemData),
                rankings: $this->extractRankings($itemData),
                rawData: $itemData
            );
        } catch (\InvalidArgumentException $e) {
            throw new AmazonDataValidationException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateProductData(array $itemData): bool
    {
        $requiredFields = [
            'ASIN',
            'ItemInfo.Title.DisplayValue',
            'ItemInfo.ByLineInfo.Brand.DisplayValue',
            'DetailPageURL'
        ];

        foreach ($requiredFields as $field) {
            if (!$this->getNestedValue($itemData, $field)) {
                throw new AmazonDataValidationException("Campo requerido faltante: $field");
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransformProductData(AmazonProductData $productData): array
    {
        $this->validateProductDataForExport($productData);

        try {
            // Partimos de los datos originales y aplicamos modificaciones
            $amazonData = $productData->rawData;

            // Aplicar transformaciones inversas completas
            $amazonData = $this->applyReverseTransformations($amazonData, $productData);

            return $amazonData;

        } catch (\Exception $e) {
            throw new AmazonDataValidationException('Error en transformación inversa: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateProductDataForExport(AmazonProductData $productData): bool
    {
        $requiredFields = ['asin', 'title', 'brand', 'detailPageUrl'];

        foreach ($requiredFields as $field) {
            if (empty($productData->$field)) {
                throw new AmazonDataValidationException("Campo requerido faltante para exportación: $field");
            }
        }

        // Validar que tenemos datos originales para reconstruir la estructura
        if (empty($productData->rawData)) {
            throw new AmazonDataValidationException('Datos originales no disponibles para exportación');
        }

        return true;
    }

    /**
     * Aplica las transformaciones inversas completas a los datos originales
     * Reconstruye la estructura exacta de Amazon con las modificaciones aplicadas
     */
    private function applyReverseTransformations(array $amazonData, AmazonProductData $productData): array
    {
        $exportData = $amazonData;

        // Actualizar campos básicos que pueden haber sido modificados
        $exportData['ASIN'] = $productData->asin;
        $exportData['DetailPageURL'] = $productData->detailPageUrl;

        // Actualizar información del producto (ItemInfo)
        if (isset($exportData['ItemInfo'])) {
            // Título
            if (isset($exportData['ItemInfo']['Title'])) {
                $exportData['ItemInfo']['Title']['DisplayValue'] = $productData->title;
            }

            // Marca
            if (isset($exportData['ItemInfo']['ByLineInfo']['Brand'])) {
                $exportData['ItemInfo']['ByLineInfo']['Brand']['DisplayValue'] = $productData->brand;
            }

            // Fabricante
            if (isset($exportData['ItemInfo']['ByLineInfo']['Manufacturer'])) {
                $exportData['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'] = $productData->manufacturer ?? '';
            }

            // Características
            if (isset($exportData['ItemInfo']['Features'])) {
                $exportData['ItemInfo']['Features']['DisplayValues'] = $productData->features;
            }
        }

        // Reconstruir información de imágenes manteniendo estructura original
        if (isset($exportData['Images'])) {
            $exportData['Images'] = $this->reconstructImages($productData->images, $exportData['Images']);
        }

        // 4. Reconstruir información de precios manteniendo estructura original
        if (isset($exportData['Offers']['Listings'][0]) && !empty($productData->prices)) {
            $exportData['Offers']['Listings'][0] = $this->reconstructPrices(
                $productData->prices[0],
                $exportData['Offers']['Listings'][0]
            );
        }

        // Reconstruir información de rankings manteniendo estructura original
        if (isset($exportData['BrowseNodeInfo']['BrowseNodes'][0]) && !empty($productData->rankings)) {
            $exportData['BrowseNodeInfo']['BrowseNodes'][0] = $this->reconstructRankings(
                $productData->rankings[0],
                $exportData['BrowseNodeInfo']['BrowseNodes'][0]
            );
        }

        // Asegurar campos requeridos por la estructura de Amazon
        $exportData = $this->ensureRequiredFields($exportData);

        return $exportData;
    }

    /**
     * Reconstruye la estructura de imágenes manteniendo el formato original
     */
    private function reconstructImages(array $currentImages, array $originalImages): array
    {
        // Si no hay imágenes actuales, mantener las originales
        if (empty($currentImages)) {
            return $originalImages;
        }

        // Reconstruir estructura de imágenes con datos actuales pero formato original
        $reconstructed = $originalImages;

        // Actualizar imagen primaria si existe
        if (isset($currentImages[0]) && isset($reconstructed['Primary']['Large'])) {
            $currentImage = $currentImages[0];
            $reconstructed['Primary']['Large'] = [
                'Height' => $currentImage['height'] ?? 500,
                'URL' => $currentImage['url'] ?? '',
                'Width' => $currentImage['width'] ?? 500
            ];
        }

        return $reconstructed;
    }

    /**
     * Reconstruye la estructura de precios manteniendo el formato original
     */
    private function reconstructPrices(array $currentPrice, array $originalPrice): array
    {
        $reconstructed = $originalPrice;

        // Actualizar campos de precio
        if (isset($reconstructed['Price'])) {
            $reconstructed['Price']['Amount'] = $currentPrice['amount'] ?? 0;
            $reconstructed['Price']['DisplayAmount'] = $currentPrice['display_amount'] ?? '0,00 €';

            // Mantener ahorros originales o establecer valores por defecto
            if (isset($currentPrice['savings_amount']) && $currentPrice['savings_amount'] > 0) {
                $reconstructed['Price']['Savings'] = [
                    'Amount' => $currentPrice['savings_amount'],
                    'Currency' => 'EUR',
                    'DisplayAmount' => $currentPrice['savings_display'] ?? '0,00 € (0%)',
                    'Percentage' => $currentPrice['savings_percentage'] ?? 0
                ];
            }
        }

        // Mantener información de envío
        $reconstructed['DeliveryInfo']['IsFreeShippingEligible'] = $currentPrice['is_free_shipping'] ?? true;
        $reconstructed['ViolatesMAP'] = $currentPrice['violates_map'] ?? false;

        return $reconstructed;
    }

    /**
     * Reconstruye la estructura de rankings manteniendo el formato original
     */
    private function reconstructRankings(array $currentRanking, array $originalRanking): array
    {
        return [
            'ContextFreeName' => $currentRanking['context_free_name'] ?? $originalRanking['ContextFreeName'] ?? 'Barras de sonido',
            'DisplayName' => $currentRanking['category_name'] ?? $originalRanking['DisplayName'] ?? 'Barras de sonido',
            'Id' => $currentRanking['category_id'] ?? $originalRanking['Id'] ?? '1384102031',
            'IsRoot' => $currentRanking['is_root'] ?? $originalRanking['IsRoot'] ?? false,
            'SalesRank' => $currentRanking['sales_rank'] ?? $originalRanking['SalesRank'] ?? 1
        ];
    }

    /**
     * Asegura que todos los campos requeridos por Amazon estén presentes
     */
    private function ensureRequiredFields(array $data): array
    {
        // Campos requeridos mínimos
        $required = [
            'ASIN' => '',
            'DetailPageURL' => '',
            'ItemInfo.Title.DisplayValue' => '',
            'ItemInfo.ByLineInfo.Brand.DisplayValue' => '',
            'Offers.Listings.0.Price.Amount' => 0
        ];

        foreach ($required as $path => $defaultValue) {
            if ($this->getNestedValue($data, $path) === null) {
                $data = $this->setNestedValue($data, $path, $defaultValue);
            }
        }

        return $data;
    }

    /**
     * Establece un valor en una estructura anidada
     */
    private function setNestedValue(array $array, string $keyPath, $value): array
    {
        $keys = explode('.', $keyPath);
        $current = &$array;

        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current = $value;
        return $array;
    }

    private function extractImages(array $itemData): array
    {
        $images = [];

        if (isset($itemData['Images']['Primary']['Large'])) {
            $largeImage = $itemData['Images']['Primary']['Large'];
            $images[] = [
                'url' => $largeImage['URL'],
                'width' => $largeImage['Width'],
                'height' => $largeImage['Height'],
                'type' => 'large',
                'is_primary' => true
            ];
        }

        return $images;
    }

    private function extractPrices(array $itemData): array
    {
        $prices = [];

        if (isset($itemData['Offers']['Listings'][0])) {
            $listing = $itemData['Offers']['Listings'][0];
            $priceData = $listing['Price'];

            $prices[] = [
                'listing_id' => $listing['Id'],
                'amount' => $priceData['Amount'],
                'currency' => $priceData['Currency'],
                'display_amount' => $priceData['DisplayAmount'],
                'savings_amount' => $priceData['Savings']['Amount'] ?? null,
                'savings_display' => $priceData['Savings']['DisplayAmount'] ?? null,
                'savings_percentage' => $priceData['Savings']['Percentage'] ?? null,
                'is_free_shipping' => $listing['DeliveryInfo']['IsFreeShippingEligible'] ?? false,
                'violates_map' => $listing['ViolatesMAP'] ?? false
            ];
        }

        return $prices;
    }

    private function extractRankings(array $itemData): array
    {
        $rankings = [];

        if (isset($itemData['BrowseNodeInfo']['BrowseNodes'][0])) {
            $browseNode = $itemData['BrowseNodeInfo']['BrowseNodes'][0];

            $rankings[] = [
                'category_id' => $browseNode['Id'],
                'category_name' => $browseNode['DisplayName'],
                'context_free_name' => $browseNode['ContextFreeName'] ?? null,
                'sales_rank' => $browseNode['SalesRank'],
                'is_root' => $browseNode['IsRoot']
            ];
        }

        return $rankings;
    }

    private function getNestedValue(array $array, string $keyPath, $default = null)
    {
        $keys = explode('.', $keyPath);
        $current = $array;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }
}