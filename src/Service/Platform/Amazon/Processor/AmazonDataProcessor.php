<?php

namespace App\Service\Platform\Amazon\Processor;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;
use App\Service\Platform\Amazon\Transformer\AmazonProductTransformerInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementación del procesador de datos de Amazon
 * Maneja la transformación bidireccional de datos
 * (Importación: JSON → Entidades, Exportación: Entidades → JSON)
 */
class AmazonDataProcessor implements AmazonDataProcessorInterface
{
    public function __construct(
        private AmazonProductTransformerInterface $transformer,
        private ?LoggerInterface                  $logger = null
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processItem(array $itemData): AmazonProductData
    {
        try {
            $this->logger?->debug('Procesando item Amazon', ['asin' => $itemData['ASIN'] ?? 'unknown']);

            return $this->transformer->transformProductData($itemData);

        } catch (AmazonDataValidationException $e) {
            $this->logger?->warning('Error validación datos Amazon', [
                'asin' => $itemData['ASIN'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error('Error procesando item Amazon', [
                'asin' => $itemData['ASIN'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw new AmazonDataValidationException('Error procesando item: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processItems(array $itemsData): array
    {
        $processedItems = [];
        $errors = [];

        foreach ($itemsData as $index => $itemData) {
            try {
                $processedItems[] = $this->processItem($itemData);
            } catch (AmazonDataValidationException $e) {
                $errors[] = [
                    'index' => $index,
                    'asin' => $itemData['ASIN'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                $this->logger?->error('Error procesando item en lote', [
                    'index' => $index,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($errors)) {
            $this->logger?->warning('Errores durante procesamiento por lote', [
                'total_errors' => count($errors),
                'total_items' => count($itemsData)
            ]);
        }

        return $processedItems;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseProcessItem(AmazonProductData $productData): array
    {
        try {
            $this->logger?->debug('Transformando producto a formato Amazon', ['asin' => $productData->asin]);

            // Obtener datos originales y aplicar modificaciones
            $amazonData = $productData->rawData;
            return $this->applyModifications($amazonData, $productData);

        } catch (\Exception $e) {
            $this->logger?->error('Error transformando producto a formato Amazon', [
                'asin' => $productData->asin,
                'error' => $e->getMessage()
            ]);
            throw new AmazonDataValidationException('Error transformando producto: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reverseProcessItems(array $productsData): array
    {
        $processedItems = [];
        $errors = [];

        foreach ($productsData as $index => $productData) {
            try {
                $processedItems[] = $this->reverseProcessItem($productData);
            } catch (AmazonDataValidationException $e) {
                $errors[] = [
                    'index' => $index,
                    'asin' => $productData->asin,
                    'error' => $e->getMessage()
                ];
                $this->logger?->error('Error transformando producto en lote', [
                    'index' => $index,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($errors)) {
            $this->logger?->warning('Errores durante transformación inversa por lote', [
                'total_errors' => count($errors),
                'total_items' => count($productsData)
            ]);
        }

        return $processedItems;
    }

    /**
     * Aplica modificaciones a los datos originales de Amazon
     * Sobrescribe los campos que han sido modificados en el panel administrativo
     *
     * @param array $originalData Datos originales de Amazon
     * @param AmazonProductData $productData Datos actualizados del producto
     * @return array Datos combinados en formato Amazon
     */
    private function applyModifications(array $originalData, AmazonProductData $productData): array
    {
        $modifiedData = $originalData;

        // Actualizar información básica del producto
        $modifiedData['ASIN'] = $productData->asin;
        $modifiedData['DetailPageURL'] = $productData->detailPageUrl;

        // Actualizar información del item (título, marca, fabricante)
        if (isset($modifiedData['ItemInfo'])) {
            // Título
            if (isset($modifiedData['ItemInfo']['Title'])) {
                $modifiedData['ItemInfo']['Title']['DisplayValue'] = $productData->title;
                $modifiedData['ItemInfo']['Title']['Label'] = 'Title';
                $modifiedData['ItemInfo']['Title']['Locale'] = 'es_ES';
            }

            // Información de marca y fabricante
            if (isset($modifiedData['ItemInfo']['ByLineInfo'])) {
                // Brand
                if (isset($modifiedData['ItemInfo']['ByLineInfo']['Brand'])) {
                    $modifiedData['ItemInfo']['ByLineInfo']['Brand']['DisplayValue'] = $productData->brand;
                    $modifiedData['ItemInfo']['ByLineInfo']['Brand']['Label'] = 'Brand';
                    $modifiedData['ItemInfo']['ByLineInfo']['Brand']['Locale'] = 'es_ES';
                }

                // Manufacturer
                if (isset($modifiedData['ItemInfo']['ByLineInfo']['Manufacturer'])) {
                    $modifiedData['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'] = $productData->manufacturer ?? '';
                    $modifiedData['ItemInfo']['ByLineInfo']['Manufacturer']['Label'] = 'Manufacturer';
                    $modifiedData['ItemInfo']['ByLineInfo']['Manufacturer']['Locale'] = 'es_ES';
                }
            }

            // Características (features)
            if (isset($modifiedData['ItemInfo']['Features'])) {
                $modifiedData['ItemInfo']['Features']['DisplayValues'] = $productData->features;
                $modifiedData['ItemInfo']['Features']['Label'] = 'Features';
                $modifiedData['ItemInfo']['Features']['Locale'] = 'es_ES';
            }
        }

        // Actualizar información de precios y ofertas - CORREGIDO
        if (!empty($productData->prices) && isset($modifiedData['Offers']['Listings'])) {
            $currentPrice = $productData->prices[0]; // Tomar el primer precio

            $modifiedData['Offers']['Listings'][0] = [
                'Id' => $currentPrice['listing_id'],
                'DeliveryInfo' => [
                    'IsFreeShippingEligible' => $currentPrice['is_free_shipping']
                ],
                'Price' => [
                    'Amount' => $currentPrice['amount'], // Mantener el valor real
                    'Currency' => $currentPrice['currency'],
                    'DisplayAmount' => $currentPrice['display_amount'],
                    'Savings' => [
                        'Amount' => $currentPrice['savings_amount'] ?? 0,
                        'Currency' => $currentPrice['currency'],
                        'DisplayAmount' => $currentPrice['savings_display'] ?? '0,00 € (0%)',
                        'Percentage' => $currentPrice['savings_percentage'] ?? 0
                    ]
                ],
                'ViolatesMAP' => $currentPrice['violates_map']
            ];
        }

        // Actualizar información de imágenes - CORREGIDO
        if (!empty($productData->images) && isset($modifiedData['Images']['Primary']['Large'])) {
            $primaryImage = null;

            // Buscar imagen principal
            foreach ($productData->images as $image) {
                if ($image['is_primary']) {
                    $primaryImage = $image;
                    break;
                }
            }

            // Si no hay imagen principal, usar la primera
            if (!$primaryImage && !empty($productData->images)) {
                $primaryImage = $productData->images[0];
            }

            if ($primaryImage) {
                $modifiedData['Images']['Primary']['Large'] = [
                    'Height' => $primaryImage['height'],
                    'URL' => $primaryImage['url'],
                    'Width' => $primaryImage['width']
                ];
            }
        }

        // Actualizar información de categoría y ranking - CORREGIDO
        if (!empty($productData->rankings) && isset($modifiedData['BrowseNodeInfo']['BrowseNodes'])) {
            $ranking = $productData->rankings[0]; // Tomar el primer ranking

            $modifiedData['BrowseNodeInfo']['BrowseNodes'][0] = [
                'ContextFreeName' => $ranking['context_free_name'] ?? $ranking['category_name'],
                'DisplayName' => $ranking['category_name'],
                'Id' => $ranking['category_id'],
                'IsRoot' => $ranking['is_root'],
                'SalesRank' => $ranking['sales_rank']
            ];
        }

        return $modifiedData;
    }
}