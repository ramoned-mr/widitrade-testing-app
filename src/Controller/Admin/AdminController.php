<?php

namespace App\Controller\Admin;

use App\Form\Admin\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_index')]
    public function admin_index(): Response
    {
        // Si el usuario ya está autenticado, ir al dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_user_index');
        }

        // Si no está autenticado, ir al login de admin
        return $this->redirectToRoute('admin_login');
    }

    #[Route('/login', name: 'admin_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginFormType::class);

        if ($lastUsername) {
            $form->get('_username')->setData($lastUsername);
        }

        $form->handleRequest($request);

        $errors = [];

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $formError) {
                $errors[] = $formError->getMessage();
            }
        }

        return $this->render('admin/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $error,
            'errors' => $errors
        ]);
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}