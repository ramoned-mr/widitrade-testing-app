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

        // Actualizar información de precios y ofertas
        if (isset($modifiedData['Offers']['Listings'][0])) {
            $listing = &$modifiedData['Offers']['Listings'][0];

            // Actualizar información de envío
            if (isset($listing['DeliveryInfo'])) {
                $listing['DeliveryInfo']['IsFreeShippingEligible'] = true; // Siempre gratis para exportación
            }

            // Mantener el ID original de la listing
            $originalListingId = $listing['Id'] ?? '';

            // Reconstruir la listing con datos actualizados pero mantener ID original
            $listing = [
                'Id' => $originalListingId,
                'DeliveryInfo' => [
                    'IsFreeShippingEligible' => true
                ],
                'Price' => [
                    'Amount' => 0, // Se actualizará con datos reales si están disponibles
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
            ];
        }

        // Actualizar información de categoría y ranking
        if (isset($modifiedData['BrowseNodeInfo']['BrowseNodes'][0])) {
            $browseNode = &$modifiedData['BrowseNodeInfo']['BrowseNodes'][0];

            // Mantener la estructura pero asegurar campos requeridos
            $browseNode = [
                'ContextFreeName' => $browseNode['ContextFreeName'] ?? 'Electrónica',
                'DisplayName' => $browseNode['DisplayName'] ?? 'Barras de sonido',
                'Id' => $browseNode['Id'] ?? '1384102031',
                'IsRoot' => $browseNode['IsRoot'] ?? false,
                'SalesRank' => $browseNode['SalesRank'] ?? 1
            ];
        }

        $modifiedData['Images'] = $originalData['Images'] ?? [];
        //$modifiedData['__type'] = $originalData['__type'] ?? '';

        return $modifiedData;
    }
}