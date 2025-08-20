<?php

namespace App\Service\Platform\Amazon\Transformer;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;
use Psr\Log\LoggerInterface;

/**
 * Implementación del transformador de datos de Amazon
 */
class AmazonProductTransformer implements AmazonProductTransformerInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null
    )
    {
    }

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