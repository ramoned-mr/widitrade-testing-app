<?php

namespace App\Controller\Admin\Contact;

use App\Entity\Contact;
use App\Form\Admin\Contact\ContactType;
use App\Repository\ContactRepository;
use App\Service\Admin\ExcelManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contact')]
class ContactController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'admin_contact_index', methods: ['GET'])]
    public function index(ContactRepository $contactRepository): Response
    {
        $contacts = $contactRepository->findAll();

        return $this->render('admin/contact/index.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/new', name: 'admin_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        $errors = [];
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {

                    $contact->setCreatedAt(new \DateTimeImmutable());
                    $contact->setUpdatedAt(new \DateTime());


                    $this->em->persist($contact);
                    $this->em->flush();

                    $this->addFlash('success', 'Contact creado correctamente.');
                    return $this->redirectToRoute('admin_contact_index');
                } catch (\Exception $e) {
                    $errors[] = 'Ha ocurrido un error al crear el Contact. Por favor inténtalo nuevamente.';
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        if ($isAjax) {
            return $this->render('admin/contact/_new.html.twig', [
                'contact' => $contact,
                'form' => $form,
                'error_new' => !empty($errors) ? $errors[0] : null,
            ]);
        }

        return $this->redirectToRoute('admin_contact_index');
    }

    #[Route('/{id}/show', name: 'admin_contact_show', methods: ['GET'])]
    public function show(Contact $contact, Request $request): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_contact_index');
        }

        return $this->render('admin/contact/_show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_contact_index');
        }

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        $error_edit = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {

                    $contact->setUpdatedAt(new \DateTime());


                    $this->em->flush();

                    if ($request->isXmlHttpRequest()) {
                        return new Response('', Response::HTTP_OK);
                    }

                    $this->addFlash('success', 'Contact actualizado correctamente.');
                    return $this->redirectToRoute('admin_contact_index');
                } catch (\Exception $e) {
                    $error_edit = 'Ha ocurrido un error al actualizar el contact: ' . $e->getMessage();
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $error_edit = $error->getMessage();
                    break;
                }
            }
        }

        return $this->render('admin/contact/_edit.html.twig', [
            'contact' => $contact,
            'form' => $form->createView(),
            'error_edit' => $error_edit
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact): Response
    {
        if ($this->isCsrfTokenValid('delete' . $contact->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($contact);
                $this->em->flush();
                $this->addFlash('success', 'Contact eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo eliminar el contact. Error: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_contact_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}/set-status', name: 'admin_contact_set_status', methods: ['GET', 'POST'])]
    public function setStatus(Contact $contact): Response
    {
        $contact->setIsActive(!$contact->isActive());

        $contact->setUpdatedAt(new \DateTime());


        $this->em->flush();

        $status = $contact->isActive() ? 'activado' : 'desactivado';
        $this->addFlash('success', 'Contact ' . $status . ' correctamente.');

        return $this->redirectToRoute('admin_contact_index');
    }


    #[Route('/excel', name: 'admin_contact_excel', methods: ['GET', 'POST'])]
    public function generateExcel(ContactRepository $contactRepository): Response
    {
        $contactsArray = $contactRepository->findAll();
        $contacts = [];

        for ($i = 0; $i < count($contactsArray); $i++) {
            $contacts[$i]["ID"] = $contactsArray[$i]->getId();
            $contacts[$i]["Usuario"] = $contactsArray[$i]->getUser()->getFullname();
            $contacts[$i]["Mensaje"] = $contactsArray[$i]->getMessage() instanceof \Stringable ? $contactsArray[$i]->getMessage()->__toString() : ($contactsArray[$i]->getMessage() === null ? '' : (string)$contactsArray[$i]->getMessage());
            $contacts[$i]["Tipo de Consulta"] = $contactsArray[$i]->getQuestionType() instanceof \Stringable ? $contactsArray[$i]->getQuestionType()->__toString() : ($contactsArray[$i]->getQuestionType() === null ? '' : (string)$contactsArray[$i]->getQuestionType());
            $contacts[$i]["Fecha de Creación"] = $contactsArray[$i]->getCreatedAt() ? $contactsArray[$i]->getCreatedAt()->format("d/m/Y H:i:s") : '';
            $contacts[$i]["Fecha de Actualización"] = $contactsArray[$i]->getUpdatedAt() ? $contactsArray[$i]->getUpdatedAt()->format("d/m/Y H:i:s") : '';
            $contacts[$i]["Status"] = $contactsArray[$i]->isActive() ? "Activo" : "Inactivo";
        }

        $excelManager = new ExcelManager($contacts);
        $temp_file = $excelManager->generateExcel();

        return $this->file($temp_file, "Contacts_" . ($_ENV['WEB_NAME'] ?? 'app') . "_" . date('Y-m-d') . ".xlsx", ResponseHeaderBag::DISPOSITION_INLINE);
    }
}