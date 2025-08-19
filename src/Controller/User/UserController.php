<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\User\ForgotPasswordFormType;
use App\Form\User\LoginFormType;
use App\Form\User\ProfileFormType;
use App\Form\User\RegistrationFormType;
use App\Form\User\ResetPasswordFormType;
use App\Repository\UserRepository;
use App\Service\User\EmailManager;
use App\Service\User\TokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private EmailManager $emailManager;

    public function __construct(
        EntityManagerInterface $em,
        EmailManager           $emailManager
    )
    {
        $this->em = $em;
        $this->emailManager = $emailManager;
    }

    #[Route('/', name: 'user_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
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

        return $this->render('user/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $error,
            'errors' => $errors
        ]);
    }

    #[Route('/logout', name: 'user_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/registro', name: 'user_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $errors = [];

        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
            if ($email && $userRepository->findOneBy(['email' => $email])) {
                $form->get('email')->addError(new \Symfony\Component\Form\FormError(
                    'Este correo electrónico ya está registrado. Por favor introduce uno válido.'
                ));
            }

            if ($form->isValid()) {
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $this->em->persist($user);
                $this->em->flush();

                $loginUrl = $this->generateUrl('user_login', [], 0);

                if ($user->allowsEmails()) {
                    $emailSent = $this->emailManager->sendEmail(
                        'Registro Completado | ' . ($_ENV["WEB_NAME"] ?? 'Nuestra plataforma'),
                        'emails/registration_completed.html.twig',
                        $user->getEmail(),
                        [
                            'user' => $user,
                            'loginUrl' => $loginUrl
                        ]
                    );
                }

                if (!$emailSent && $_ENV['APP_ENV'] === 'dev') {
                    $error = $this->emailManager->getLastError();
                    $this->addFlash('error', 'DEBUG: Error al enviar email: ' . ($error ?? 'Desconocido'));
                }

                $this->addFlash('success', 'Ya puedes iniciar sesión con tus credenciales.');
                return $this->redirectToRoute('user_login');
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        return $this->render('user/register.html.twig', ['registrationForm' => $form->createView(),
            'errors' => $errors]);
    }

    #[
        Route('/password/forgot', name: 'user_forgot_password')]
    public function forgotPassword(
        Request        $request,
        UserRepository $userRepository,
        TokenGenerator $tokenGenerator,
    ): Response
    {
        $form = $this->createForm(ForgotPasswordFormType::class);

        $errors = [];
        $lastEmail = '';

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('forgot_password', $submittedToken)) {
                $errors[] = 'El token CSRF no es válido. Por favor, prueba a enviar nuevamente el formulario.';
            } else {
                $email = $request->request->get('email');
                $lastEmail = $email;

                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Por favor ingresa un correo electrónico válido';
                } else {
                    $user = $userRepository->findOneBy(['email' => $email]);

                    if ($user) {
                        if (!$user->isActive()) {
                            $this->addFlash('error', 'Tu cuenta ha sido desactivada. Por favor, contacta con el administrador.');
                            return $this->redirectToRoute('user_login');
                        }

                        $user->setToken($tokenGenerator->generate());
                        $this->em->flush();

                        $resetLink = $this->generateUrl('user_reset_password', [
                            'token' => $user->getToken()
                        ], 0);

                        if ($user->allowsEmails()) {
                            $emailSent = $this->emailManager->sendEmail(
                                'Recuperación de Contraseña en ' . ($_ENV["WEB_NAME"] ?? 'Nuestra plataforma'),
                                'emails/reset_password.html.twig',
                                $user->getEmail(),
                                [
                                    'user' => $user,
                                    'resetLink' => $resetLink,
                                    'tokenExpiration' => '24 horas'
                                ]
                            );
                        }

                        if (!$emailSent && $_ENV['APP_ENV'] === 'dev') {
                            $error = $this->emailManager->getLastError();
                            $this->addFlash('error', 'DEBUG: Error al enviar email de recuperación: ' . ($error ?? 'Desconocido'));
                        }

                        $this->addFlash('success', 'Se ha enviado un correo electrónico con instrucciones para restablecer tu contraseña.');
                    } else {
                        $this->addFlash('success', 'Si tu correo está registrado, recibirás un email con instrucciones para restablecer tu contraseña.');
                    }

                    return $this->redirectToRoute('user_login');
                }
            }
        }

        return $this->render('user/forgot_password.html.twig', [
            'forgotPasswordForm' => $form->createView(),
            'errors' => $errors,
            'last_email' => $lastEmail
        ]);
    }

    #[Route('/password/reset/{token}', name: 'user_reset_password')]
    public function resetPassword(
        Request                     $request,
        string                      $token,
        UserRepository              $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        TokenGenerator              $tokenGenerator
    ): Response
    {
        $user = $userRepository->findOneBy(['token' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('El enlace para restablecer la contraseña no es válido o ha expirado.');
        }

        if (!$user->isActive()) {
            $this->addFlash('error', 'Tu cuenta ha sido desactivada. Por favor, contacta con el administrador.');
            return $this->redirectToRoute('user_login');
        }

        $form = $this->createForm(ResetPasswordFormType::class);

        $errors = [];

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('reset_password', $submittedToken)) {
                $errors[] = 'El token CSRF no es válido. Por favor, prueba nuevamente.';
            } else {
                $postData = $request->request->all();

                $formData = $postData['reset_password_form'] ?? [];
                $plainPassword = is_array($formData) && isset($formData['plainPassword']) ? $formData['plainPassword'] : [];

                $first = is_array($plainPassword) && isset($plainPassword['first']) ? $plainPassword['first'] : null;
                $second = is_array($plainPassword) && isset($plainPassword['second']) ? $plainPassword['second'] : null;

                if (empty($first) && empty($second)) {
                    $errors[] = 'Por favor completa ambos campos de contraseña.';
                } elseif (empty($first)) {
                    $errors[] = 'Por favor ingresa una contraseña en el primer campo.';
                } elseif (empty($second)) {
                    $errors[] = 'Por favor confirma tu contraseña en el segundo campo.';
                } elseif (strlen($first) < 8) {
                    $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
                } elseif ($first !== $second) {
                    $errors[] = 'Las contraseñas no coinciden. Por favor, verifica que ambas sean iguales.';
                } else {
                    $user->setPassword(
                        $passwordHasher->hashPassword(
                            $user,
                            $first
                        )
                    );

                    $user->setToken($tokenGenerator->generate());
                    $this->em->flush();

                    $this->addFlash('success', 'Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.');
                    return $this->redirectToRoute('user_login');
                }
            }
        }

        return $this->render('user/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView(),
            'errors' => $errors
        ]);
    }

    #[Route('/mi-perfil', name: 'user_profile')]
    public function profile(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('user_login');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        $errors = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            $user->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Tu perfil ha sido actualizado correctamente.');

            return $this->redirectToRoute('user_profile');
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
        }

        return $this->render('user/profile.html.twig', [
            'profileForm' => $form->createView(),
            'errors' => $errors,
            'user' => $user
        ]);
    }
}