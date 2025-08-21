<?php

namespace App\Service\Platform\Amazon\Frontend;

use Psr\Log\LoggerInterface;

/**
 * Servicio para formatear datos de productos para la vista
 * Implementa ProductFormatterServiceInterface
 */
class ProductFormatterService implements ProductFormatterServiceInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function formatPriceInfo(object $product): array
    {
        try {
            $priceInfo = [
                'current_price' => null,
                'display_price' => 'Precio no disponible',
                'original_price' => null,
                'discount_amount' => null,
                'discount_percentage' => null,
                'discount_display' => null,
                'free_shipping' => false,
                'currency' => 'EUR'
            ];

            // Obtener el primer precio activo
            $activePrice = null;
            foreach ($product->getPrices() as $price) {
                if ($price->isActive() && $price->getAmount() > 0) {
                    $activePrice = $price;
                    break;
                }
            }

            if ($activePrice) {
                $priceInfo['current_price'] = $activePrice->getAmount();
                $priceInfo['display_price'] = $activePrice->getDisplayAmount() ?: $this->formatPrice($activePrice->getAmount(), $activePrice->getCurrency());
                $priceInfo['currency'] = $activePrice->getCurrency();
                $priceInfo['free_shipping'] = $activePrice->getIsFreeShipping();

                // Información de descuento
                if ($activePrice->getSavingsAmount() && $activePrice->getSavingsAmount() > 0) {
                    $priceInfo['discount_amount'] = $activePrice->getSavingsAmount();
                    $priceInfo['discount_percentage'] = $activePrice->getSavingsPercentage();
                    $priceInfo['discount_display'] = $activePrice->getSavingsDisplay();
                    $priceInfo['original_price'] = $activePrice->getAmount() + $activePrice->getSavingsAmount();
                }

                $this->logger?->debug('Precio formateado', [
                    'asin' => $product->getAsin(),
                    'price_info' => $priceInfo
                ]);
            }

            return $priceInfo;

        } catch (\Exception $e) {
            $this->logger?->error('Error formateando precio', [
                'asin' => $product->getAsin(),
                'error' => $e->getMessage()
            ]);

            return [
                'current_price' => null,
                'display_price' => 'Precio no disponible',
                'free_shipping' => false,
                'currency' => 'EUR'
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function formatFeatures(array $features, int $maxVisible = 3): array
    {
        $cleanFeatures = array_filter($features, function ($feature) {
            return !empty(trim($feature));
        });

        return [
            'visible' => array_slice($cleanFeatures, 0, $maxVisible),
            'hidden' => array_slice($cleanFeatures, $maxVisible),
            'total_count' => count($cleanFeatures),
            'has_more' => count($cleanFeatures) > $maxVisible
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryImage(object $product): array
    {
        $defaultImage = [
            'url' => '/assets/images/no-product-image.jpg',
            'alt' => 'Imagen no disponible',
            'width' => 500,
            'height' => 500
        ];

        try {
            // Buscar imagen primaria activa
            foreach ($product->getImages() as $image) {
                if ($image->isActive() && $image->getIsPrimary() && !empty($image->getUrl())) {
                    return [
                        'url' => $image->getUrl(),
                        'alt' => $image->getAltText() ?: $product->getTitle(),
                        'width' => $image->getWidth(),
                        'height' => $image->getHeight()
                    ];
                }
            }

            // Si no hay primaria, buscar la primera imagen activa
            foreach ($product->getImages() as $image) {
                if ($image->isActive() && !empty($image->getUrl())) {
                    return [
                        'url' => $image->getUrl(),
                        'alt' => $image->getAltText() ?: $product->getTitle(),
                        'width' => $image->getWidth(),
                        'height' => $image->getHeight()
                    ];
                }
            }

        } catch (\Exception $e) {
            $this->logger?->error('Error obteniendo imagen principal', [
                'asin' => $product->getAsin(),
                'error' => $e->getMessage()
            ]);
        }

        return $defaultImage;
    }

    /**
     * {@inheritdoc}
     */
    public function formatTitle(string $title, int $maxLength = 80): string
    {
        $cleanTitle = trim($title);

        if (mb_strlen($cleanTitle) <= $maxLength) {
            return $cleanTitle;
        }

        return mb_substr($cleanTitle, 0, $maxLength - 3) . '...';
    }

    /**
     * {@inheritdoc}
     */
    public function formatProductForDisplay(object $product, int $position, array $rating): array
    {
        return [
            'position' => $position,
            'asin' => $product->getAsin(),
            'title' => $this->formatTitle($product->getTitle()),
            'full_title' => $product->getTitle(),
            'brand' => $product->getBrand(),
            'amazon_url' => $this->formatAmazonUrl($product->getAmazonUrl()),
            'image' => $this->getPrimaryImage($product),
            'price' => $this->formatPriceInfo($product),
            'features' => $this->formatFeatures($product->getFeatures()),
            'rating' => $rating,
            'special_badge' => $rating['special_badge'] ?? null,
            'ranking_info' => $this->formatRankingInfo($product)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function formatAmazonUrl(string $amazonUrl): string
    {
        // Si ya tiene parámetros de tracking, devolverlo tal como está
        if (strpos($amazonUrl, 'tag=') !== false) {
            return $amazonUrl;
        }

        // Agregar parámetros básicos de tracking si no los tiene
        $separator = strpos($amazonUrl, '?') !== false ? '&' : '?';

        return $amazonUrl . $separator . 'tag=defaulttag-21&linkCode=osi';
    }

    /**
     * Formatea información de ranking del producto
     */
    private function formatRankingInfo(object $product): array
    {
        $rankingInfo = [
            'category' => 'General',
            'sales_rank' => null,
            'category_display' => 'General'
        ];

        try {
            // Obtener el primer ranking activo
            foreach ($product->getRankings() as $ranking) {
                if ($ranking->isActive()) {
                    $rankingInfo = [
                        'category' => $ranking->getCategoryName(),
                        'sales_rank' => $ranking->getSalesRank(),
                        'category_display' => $ranking->getContextFreeName() ?: $ranking->getCategoryName()
                    ];
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->logger?->error('Error formateando ranking info', [
                'asin' => $product->getAsin(),
                'error' => $e->getMessage()
            ]);
        }

        return $rankingInfo;
    }

    /**
     * Formatea un precio numérico
     */
    private function formatPrice(float $amount, string $currency = 'EUR'): string
    {
        return match ($currency) {
            'EUR' => number_format($amount, 2, ',', '.') . ' €',
            'USD' => '$' . number_format($amount, 2, '.', ','),
            default => number_format($amount, 2, ',', '.') . ' ' . $currency
        };
    }
}