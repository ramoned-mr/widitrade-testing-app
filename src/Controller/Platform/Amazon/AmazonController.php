<?php

namespace App\Controller\Platform\Amazon;

use App\Service\Platform\Amazon\Frontend\RankingFacadeServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AmazonController extends AbstractController
{
    public function __construct(
        private RankingFacadeServiceInterface $rankingFacadeService
    )
    {
    }

    #[Route('/home', name: 'home')]
    public function home(): Response
    {
        try {
            if (!$this->rankingFacadeService->hasAvailableProducts('Barras de sonido')) {
                $this->addFlash('warning', 'No hay productos disponibles en este momento.');

                return $this->render('platform/amazon/home.html.twig', [
                    'products' => [],
                    'stats' => [],
                    'total_products' => 0
                ]);
            }

            // Obtener TODOS los productos de barras de sonido
            $products = $this->rankingFacadeService->getAllSoundbarProducts();
            $stats = $this->rankingFacadeService->getRankingStats();
            $totalProducts = count($products);

            return $this->render('platform/amazon/home.html.twig', [
                'products' => $products,
                'stats' => $stats,
                'total_products' => $totalProducts,
                'category_description' => sprintf(
                    'Descubre nuestra selección completa de las %d mejores barras de sonido disponibles en España, cuidadosamente evaluadas y ordenadas por calidad, precio y características.',
                    $totalProducts
                )
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al cargar los productos. Por favor, inténtalo más tarde.');

            return $this->render('platform/amazon/home.html.twig', [
                'products' => [],
                'stats' => [],
                'total_products' => 0
            ]);
        }
    }

    #[Route('/categoria/{category}', name: 'ranking_by_category')]
    public function rankingByCategory(string $category): Response
    {
        try {
            // Normalizar el nombre de categoría
            $categoryDisplay = ucwords(str_replace('-', ' ', $category));

            if (!$this->rankingFacadeService->hasAvailableProducts($categoryDisplay)) {
                $this->addFlash('warning', "No hay productos disponibles en la categoría '{$categoryDisplay}'.");

                return $this->render('platform/amazon/home.html.twig', [
                    'products' => [],
                    'stats' => [],
                    'total_products' => 0
                ]);
            }

            // Obtener TODOS los productos de la categoría
            $products = $this->rankingFacadeService->getAllProductsForDisplay($categoryDisplay);
            $stats = $this->rankingFacadeService->getRankingStats();
            $totalProducts = count($products);

            return $this->render('platform/amazon/home.html.twig', [
                'products' => $products,
                'stats' => $stats,
                'total_products' => $totalProducts,
                'category_description' => sprintf(
                    'Encuentra los %d mejores productos en %s, seleccionados por calidad y valor.',
                    $totalProducts,
                    $categoryDisplay
                )
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al cargar la categoría. Por favor, inténtalo más tarde.');

            return $this->redirectToRoute('home');
        }
    }

    #[Route('/api/products/count/{category?}', name: 'api_products_count', methods: ['GET'])]
    public function getProductCount(?string $category = null): Response
    {
        try {
            $categoryDisplay = $category ? ucwords(str_replace('-', ' ', $category)) : null;
            $count = $this->rankingFacadeService->getProductCount($categoryDisplay);

            return $this->json([
                'success' => true,
                'count' => $count,
                'category' => $categoryDisplay
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error al obtener el conteo de productos',
                'count' => 0
            ], 500);
        }
    }
}