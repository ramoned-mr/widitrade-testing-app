<?php

namespace App\Controller\Admin\User;

use App\Entity\User;
use App\Form\Admin\User\UserType;
use App\Repository\UserRepository;
use App\Service\Admin\ExcelManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $errors = [];

        $email = $form->get('email')->getData();
        if ($email && $userRepository->findOneBy(['email' => $email])) {
            $form->get('email')->addError(new \Symfony\Component\Form\FormError(
                'Este correo electrónico ya está registrado.'
            ));
        }

        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    );
                    $user->setPassword($hashedPassword);

                    $this->em->persist($user);
                    $this->em->flush();

                    $this->addFlash('success', 'User creado correctamente.');
                    return $this->redirectToRoute('admin_user_index');

                } catch (\Exception $e) {
                    $errors[] = 'Ha ocurrido un error al crear el User. Por favor inténtalo nuevamente.';
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        if ($isAjax) {
            return $this->render('admin/user/_new.html.twig', [
                'user' => $user,
                'form' => $form,
                'error_new' => !empty($errors) ? $errors[0] : null,
            ]);
        }

        return $this->redirectToRoute("admin_user_index");
    }

    #[Route('/{id}/show', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user, Request $request): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/_show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_user_index');
        }

        $currentPassword = $user->getPassword();
        $userId = $user->getId();
        $form = $this->createForm(UserType::class, $user);

        if ($request->isMethod('POST')) {
            $postData = $request->request->all();
            $userData = $postData['user'] ?? [];

            if (isset($userData['password']) && empty($userData['password'])) {
                $request->request->set(
                    'user',
                    array_merge($userData, ['password' => $currentPassword])
                );
            }
        }

        $form->handleRequest($request);
        $error_edit = null;

        if ($form->isSubmitted()) {
            $emailToCheck = $form->get('email')->getData();
            $duplicateEmail = $userRepository->createQueryBuilder('u')
                ->where('u.email = :email')
                ->andWhere('u.id != :id')
                ->setParameter('email', $emailToCheck)
                ->setParameter('id', $userId)
                ->getQuery()
                ->getOneOrNullResult();

            if ($duplicateEmail) {
                $form->get('email')->addError(new \Symfony\Component\Form\FormError(
                    'Este correo electrónico ya está registrado por otro usuario.'
                ));
                $error_edit = 'El correo electrónico ya está en uso por otro usuario.';
            } elseif ($form->isValid()) {
                try {
                    if (method_exists($user, 'setUpdatedAt')) {
                        $user->setUpdatedAt(new \DateTime());
                    }

                    $submittedPassword = $form->get('password')->getData();
                    if ($submittedPassword !== $currentPassword) {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $submittedPassword)
                        );
                    }

                    $em->flush();

                    if ($request->isXmlHttpRequest()) {
                        return new Response('', Response::HTTP_OK);
                    }

                    $this->addFlash('success', 'Usuario actualizado correctamente.');
                    return $this->redirectToRoute('admin_user_index');

                } catch (\Exception $e) {
                    $error_edit = 'Ha ocurrido un error al actualizar el usuario: ' . $e->getMessage();
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $error_edit = $error->getMessage();
                    break;
                }
            }
        }

        $user->setPassword('');

        return $this->render('admin/user/_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'error_edit' => $error_edit
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($user);
                $this->em->flush();
                $this->addFlash('success', 'User eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo eliminar el user. Error: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/set-status', name: 'admin_user_set_status', methods: ['GET', 'POST'])]
    public function setStatus(User $user): Response
    {
        $user->setIsActive(!$user->isActive());
        $user->setUpdatedAt(new \DateTime());

        $this->em->flush();

        $status = $user->isActive() ? 'activado' : 'desactivado';
        $this->addFlash('success', 'User ' . $status . ' correctamente.');

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/excel', name: 'admin_user_excel', methods: ['GET', 'POST'])]
    public function generateExcel(UserRepository $userRepository): Response
    {
        $usersArray = $userRepository->findAll();
        $users = [];

        for ($i = 0; $i < count($usersArray); $i++) {
            $users[$i]["ID"] = $usersArray[$i]->getId();
            $users[$i]["Nombre"] = $usersArray[$i]->getName();
            $users[$i]["Apellidos"] = $usersArray[$i]->getLastname();
            $users[$i]["Email"] = $usersArray[$i]->getEmail();
            $users[$i]["Token"] = $usersArray[$i]->getToken();
            $users[$i]["Roles"] = json_encode($usersArray[$i]->getRoles());
            $users[$i]["Fecha Creación"] = $usersArray[$i]->getCreatedAt() ? $usersArray[$i]->getCreatedAt()->format("d/m/Y H:i:s") : '';
            $users[$i]["Fecha Actualización"] = $usersArray[$i]->getUpdatedAt() ? $usersArray[$i]->getUpdatedAt()->format("d/m/Y H:i:s") : '';
            $users[$i]["Status"] = $usersArray[$i]->isActive() ? "Activo" : "Inactivo";
        }

        $excelManager = new ExcelManager($users);
        $temp_file = $excelManager->generateExcel();

        return $this->file($temp_file, "Users_" . ($_ENV['WEB_NAME'] ?? 'app') . "_" . date('Y-m-d') . ".xlsx", ResponseHeaderBag::DISPOSITION_INLINE);
    }
}