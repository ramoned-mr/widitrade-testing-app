<?php

namespace App\Service\Platform\Amazon\Processor;

use App\Service\Platform\Amazon\ValueObject\AmazonProductData;
use App\Service\Platform\Amazon\Exception\AmazonDataValidationException;
use App\Service\Platform\Amazon\Transformer\AmazonProductTransformerInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementación del procesador de datos de Amazon
 */
class AmazonDataProcessor implements AmazonDataProcessorInterface
{
    public function __construct(
        private AmazonProductTransformerInterface $transformer,
        private ?LoggerInterface                  $logger = null
    )
    {
    }

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
}