<?php

namespace App\Service\Admin\Product;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\ProductPrice;
use App\Entity\ProductRanking;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface para el procesador de collections de productos
 * Define el contrato para manejar datos de formularios y análisis de productos
 */
interface ProductCollectionProcessorInterface
{
    /**
     * Procesa todas las collections de un producto desde la request
     *
     * @param Request $request Request con los datos del formulario
     * @param Product $product Producto a procesar
     * @return void
     */
    public function processAllCollections(Request $request, Product $product): void;

    /**
     * Procesa las imágenes desde la request
     *
     * @param Request $request Request con los datos del formulario
     * @param Product $product Producto al que asociar las imágenes
     * @return void
     */
    public function processImagesFromRequest(Request $request, Product $product): void;

    /**
     * Procesa los precios desde la request
     *
     * @param Request $request Request con los datos del formulario
     * @param Product $product Producto al que asociar los precios
     * @return void
     */
    public function processPricesFromRequest(Request $request, Product $product): void;

    /**
     * Procesa los rankings desde la request
     *
     * @param Request $request Request con los datos del formulario
     * @param Product $product Producto al que asociar los rankings
     * @return void
     */
    public function processRankingsFromRequest(Request $request, Product $product): void;

    /**
     * Procesa las características desde la request
     *
     * @param Request $request Request con los datos del formulario
     * @param Product $product Producto al que asociar las características
     * @return void
     */
    public function processFeaturesFromRequest(Request $request, Product $product): void;

    /**
     * Obtiene la imagen principal de un producto
     *
     * @param Product $product Producto del que obtener la imagen principal
     * @return ProductImage|null Imagen principal o null si no existe
     */
    public function getPrimaryImage(Product $product): ?ProductImage;

    /**
     * Obtiene el primer precio disponible de un producto
     *
     * @param Product $product Producto del que obtener el precio
     * @return ProductPrice|null Primer precio o null si no existe
     */
    public function getFirstPrice(Product $product): ?ProductPrice;

    /**
     * Obtiene el mejor ranking (número más bajo) de un producto
     *
     * @param Product $product Producto del que obtener el ranking
     * @return ProductRanking|null Mejor ranking o null si no existe
     */
    public function getBestRanking(Product $product): ?ProductRanking;
}