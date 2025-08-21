<?php

namespace App\Controller\Admin\Product;

use App\Entity\Product;
use App\Form\Admin\Product\ProductType;
use App\Repository\ProductRepository;
use App\Service\Admin\ExcelManager;
use App\Service\Admin\Product\ProductCollectionProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/product')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface              $em,
        private ProductCollectionProcessorInterface $collectionProcessor
    )
    {
    }

    #[Route('/', name: 'admin_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        $errors = [];
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $this->collectionProcessor->processAllCollections($request, $product);

                    $product->setCreatedAt(new \DateTimeImmutable());
                    $product->setUpdatedAt(new \DateTime());

                    $this->em->persist($product);
                    $this->em->flush();

                    $this->addFlash('success', 'Producto creado correctamente.');
                    return $this->redirectToRoute('admin_product_index');
                } catch (\Exception $e) {
                    $errors[] = 'Ha ocurrido un error al crear el producto: ' . $e->getMessage();
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        if ($isAjax) {
            return $this->render('admin/product/_new.html.twig', [
                'product' => $product,
                'form' => $form,
                'error_new' => !empty($errors) ? $errors[0] : null,
            ]);
        }

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_product_index');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        $error_edit = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $this->collectionProcessor->processAllCollections($request, $product);

                    $product->setUpdatedAt(new \DateTime());

                    $this->em->flush();

                    if ($request->isXmlHttpRequest()) {
                        return new Response('', Response::HTTP_OK);
                    }

                    $this->addFlash('success', 'Producto actualizado correctamente.');
                    return $this->redirectToRoute('admin_product_index');
                } catch (\Exception $e) {
                    $error_edit = 'Ha ocurrido un error al actualizar el producto: ' . $e->getMessage();
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $error_edit = $error->getMessage();
                    break;
                }
            }
        }

        return $this->render('admin/product/_edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'error_edit' => $error_edit
        ]);
    }

    #[Route('/{id}/show', name: 'admin_product_show', methods: ['GET'])]
    public function show(Product $product, Request $request): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/_show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($product);
                $this->em->flush();
                $this->addFlash('success', 'Producto eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo eliminar el producto. Error: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/set-status', name: 'admin_product_set_status', methods: ['GET', 'POST'])]
    public function setStatus(Product $product): Response
    {
        $product->setIsActive(!$product->isActive());
        $product->setUpdatedAt(new \DateTime());

        $this->em->flush();

        $status = $product->isActive() ? 'activado' : 'desactivado';
        $this->addFlash('success', 'Producto ' . $status . ' correctamente.');

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/excel', name: 'admin_product_excel', methods: ['GET', 'POST'])]
    public function generateExcel(ProductRepository $productRepository): Response
    {
        $productsArray = $productRepository->findAll();
        $products = [];

        foreach ($productsArray as $i => $product) {
            $primaryImage = $this->collectionProcessor->getPrimaryImage($product);
            $firstPrice = $this->collectionProcessor->getFirstPrice($product);
            $bestRanking = $this->collectionProcessor->getBestRanking($product);

            $products[$i] = [
                "ID" => $product->getId(),
                "ASIN" => $product->getAsin(),
                "Título" => $product->getTitle(),
                "Slug" => $product->getSlug() ?: 'No definido',
                "Marca" => $product->getBrand(),
                "Fabricante" => $product->getManufacturer() ?: 'No especificado',
                "URL Amazon" => $product->getAmazonUrl(),

                // Información de precios
                "Precio Actual" => $firstPrice ? $firstPrice->getDisplayAmount() : 'Sin precio',
                "Precio Numérico" => $firstPrice ? (float)$firstPrice->getAmount() : 0,
                "Moneda" => $firstPrice ? $firstPrice->getCurrency() : 'EUR',
                "Descuento" => $firstPrice && $firstPrice->getSavingsAmount() ? $firstPrice->getSavingsDisplay() : 'Sin descuento',
                "Envío Gratuito" => $firstPrice && $firstPrice->getIsFreeShipping() ? 'Sí' : 'No',

                // Información de ranking
                "Mejor Ranking" => $bestRanking ? '#' . $bestRanking->getSalesRank() : 'Sin ranking',
                "Categoría Ranking" => $bestRanking ? $bestRanking->getCategoryName() : 'Sin categoría',
                "ID Categoría" => $bestRanking ? $bestRanking->getCategoryId() : '',

                // Información de imágenes
                "Total Imágenes" => $product->getImages()->count(),
                "URL Imagen Principal" => $primaryImage ? $primaryImage->getUrl() : 'Sin imagen',
                "Dimensiones Imagen" => $primaryImage ? $primaryImage->getDimensions() : 'N/A',

                // Características
                "Total Características" => count($product->getFeatures()),
                "Primera Característica" => !empty($product->getFeatures()) ? substr($product->getFeatures()[0], 0, 100) . '...' : 'Sin características',

                // Información técnica
                "Fecha Creación" => $product->getCreatedAt() ? $product->getCreatedAt()->format("d/m/Y H:i:s") : '',
                "Fecha Actualización" => $product->getUpdatedAt() ? $product->getUpdatedAt()->format("d/m/Y H:i:s") : '',
                "Estado" => $product->isActive() ? "Activo" : "Inactivo",

                // Información adicional
                "Total Precios" => $product->getPrices()->count(),
                "Total Rankings" => $product->getRankings()->count(),
            ];
        }

        $excelManager = new ExcelManager($products);
        $temp_file = $excelManager->generateExcel();

        $filename = "Productos_Completo_" . ($_ENV['WEB_NAME'] ?? 'Amazon') . "_" . date('Y-m-d_H-i-s') . ".xlsx";

        return $this->file($temp_file, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}