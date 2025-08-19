<?php

namespace {{controller_namespace}};

use {{entity_full_class_name}};
use App\Form\{{entity_name}}Type;
use App\Repository\{{entity_name}}Repository;
use App\Service\Admin\ExcelManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/{{route_path}}')]
class {{controller_name}} extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: '{{route_prefix}}_index', methods: ['GET'])]
    public function index({{entity_name}}Repository ${{entity_var_singular}}Repository): Response
    {
        ${{entity_var_plural}} = ${{entity_var_singular}}Repository->findAll();

        return $this->render('{{template_path}}/index.html.twig', [
            '{{entity_var_plural}}' => ${{entity_var_plural}},
        ]);
    }

    #[Route('/new', name: '{{route_prefix}}_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        ${{entity_var_singular}} = new {{entity_name}}();
        $form = $this->createForm({{entity_name}}Type::class, ${{entity_var_singular}});
        $form->handleRequest($request);

        $errors = [];
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    {{#has_timestamps}}
                    ${{entity_var_singular}}->setCreatedAt(new \DateTimeImmutable());
                    ${{entity_var_singular}}->setUpdatedAt(new \DateTime());
                    {{/has_timestamps}}
                    
                    $this->em->persist(${{entity_var_singular}});
                    $this->em->flush();
                    
                    $this->addFlash('success', '{{entity_name}} creado correctamente.');
                    return $this->redirectToRoute('{{route_prefix}}_index');
                } catch (\Exception $e) {
                    $errors[] = 'Ha ocurrido un error al crear el {{entity_name}}. Por favor inténtalo nuevamente.';
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        if ($isAjax) {
            return $this->render('{{template_path}}/_new.html.twig', [
                '{{entity_var_singular}}' => ${{entity_var_singular}},
                'form' => $form,
                'error_new' => !empty($errors) ? $errors[0] : null,
            ]);
        }

        return $this->redirectToRoute('{{route_prefix}}_index');
    }

    #[Route('/{id}/show', name: '{{route_prefix}}_show', methods: ['GET'])]
    public function show({{entity_name}} ${{entity_var_singular}}, Request $request): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('{{route_prefix}}_index');
        }

        return $this->render('{{template_path}}/_show.html.twig', [
            '{{entity_var_singular}}' => ${{entity_var_singular}},
        ]);
    }

    #[Route('/{id}/edit', name: '{{route_prefix}}_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, {{entity_name}} ${{entity_var_singular}}): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('{{route_prefix}}_index');
        }

        $form = $this->createForm({{entity_name}}Type::class, ${{entity_var_singular}});
        $form->handleRequest($request);
        $error_edit = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    {{#has_timestamps}}
                    ${{entity_var_singular}}->setUpdatedAt(new \DateTime());
                    {{/has_timestamps}}
                    
                    $this->em->flush();

                    if ($request->isXmlHttpRequest()) {
                        return new Response('', Response::HTTP_OK);
                    }
                    
                    $this->addFlash('success', '{{entity_name}} actualizado correctamente.');
                    return $this->redirectToRoute('{{route_prefix}}_index');
                } catch (\Exception $e) {
                    $error_edit = 'Ha ocurrido un error al actualizar el {{entity_var_singular}}: ' . $e->getMessage();
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $error_edit = $error->getMessage();
                    break;
                }
            }
        }

        return $this->render('{{template_path}}/_edit.html.twig', [
            '{{entity_var_singular}}' => ${{entity_var_singular}},
            'form' => $form->createView(),
            'error_edit' => $error_edit
        ]);
    }

    #[Route('/{id}/delete', name: '{{route_prefix}}_delete', methods: ['POST'])]
    public function delete(Request $request, {{entity_name}} ${{entity_var_singular}}): Response
    {
        if ($this->isCsrfTokenValid('delete' . ${{entity_var_singular}}->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove(${{entity_var_singular}});
                $this->em->flush();
                $this->addFlash('success', '{{entity_name}} eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo eliminar el {{entity_var_singular}}. Error: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('{{route_prefix}}_index', [], Response::HTTP_SEE_OTHER);
    }

    {{#has_is_active}}
    #[Route('/{id}/set-status', name: '{{route_prefix}}_set_status', methods: ['GET', 'POST'])]
    public function setStatus({{entity_name}} ${{entity_var_singular}}): Response
    {
        ${{entity_var_singular}}->setIsActive(!${{entity_var_singular}}->isActive());
        {{#has_timestamps}}
        ${{entity_var_singular}}->setUpdatedAt(new \DateTime());
        {{/has_timestamps}}
        
        $this->em->flush();
        
        $status = ${{entity_var_singular}}->isActive() ? 'activado' : 'desactivado';
        $this->addFlash('success', '{{entity_name}} ' . $status . ' correctamente.');

        return $this->redirectToRoute('{{route_prefix}}_index');
    }
    {{/has_is_active}}

    #[Route('/excel', name: '{{route_prefix}}_excel', methods: ['GET', 'POST'])]
    public function generateExcel({{entity_name}}Repository ${{entity_var_singular}}Repository): Response
    {
        ${{entity_var_plural}}Array = ${{entity_var_singular}}Repository->findAll();
        ${{entity_var_plural}} = [];

        for ($i = 0; $i < count(${{entity_var_plural}}Array); $i++) {
            ${{entity_var_plural}}[$i]["ID"] = ${{entity_var_plural}}Array[$i]->getId();
            {{entity_fields}}
            {{#has_timestamps}}
            ${{entity_var_plural}}[$i]["Fecha Creación"] = ${{entity_var_plural}}Array[$i]->getCreatedAt() ? ${{entity_var_plural}}Array[$i]->getCreatedAt()->format("d/m/Y H:i:s") : '';
            ${{entity_var_plural}}[$i]["Fecha Actualización"] = ${{entity_var_plural}}Array[$i]->getUpdatedAt() ? ${{entity_var_plural}}Array[$i]->getUpdatedAt()->format("d/m/Y H:i:s") : '';
            {{/has_timestamps}}
            {{#has_is_active}}
            ${{entity_var_plural}}[$i]["Status"] = ${{entity_var_plural}}Array[$i]->isActive() ? "Activo" : "Inactivo";
            {{/has_is_active}}
        }

        $excelManager = new ExcelManager(${{entity_var_plural}});
        $temp_file = $excelManager->generateExcel();

        return $this->file($temp_file, "{{entity_plural_name}}_" . ($_ENV['WEB_NAME'] ?? 'app') . "_" . date('Y-m-d') . ".xlsx", ResponseHeaderBag::DISPOSITION_INLINE);
    }
}