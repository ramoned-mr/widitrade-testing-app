<?php

namespace App\Controller\User;

use App\Entity\Contact;
use App\Form\User\ContactFormType;
use App\Service\User\EmailManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EmailManager $emailManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        EmailManager           $emailManager
    )
    {
        $this->entityManager = $entityManager;
        $this->emailManager = $emailManager;
    }

    #[Route('/contacto', name: 'contact')]
    public function index(Request $request): Response
    {
        $contact = new Contact();

        if ($this->getUser()) {
            $contact->setUser($this->getUser());
        }

        $form = $this->createForm(ContactFormType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            $context = [
                'contact' => $contact,
            ];

            try {
                $this->emailManager->sendEmail(
                    'Solicitud de Contacto | ' . $_ENV["WEB_NAME"],
                    'emails/contact.html.twig',
                    $_ENV['EMAIL_RECIPIENT'],
                    $context
                );

                $this->addFlash('success', 'Tu mensaje ha sido enviado correctamente. Nos pondremos en contacto contigo pronto.');
                return $this->redirectToRoute('contact');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Ha ocurrido un error al enviar tu mensaje. Por favor, inténtalo de nuevo más tarde.');
            }
        }

        return $this->render('user/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}