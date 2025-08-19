<?php

namespace App\Controller\Admin\SuperAdmin;

use App\Entity\Admin;
use App\Form\Admin\SuperAdmin\AdminType;
use App\Repository\AdminRepository;
use App\Service\Admin\ExcelManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/manager')]
class SuperAdminController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'admin_manager_index', methods: ['GET'])]
    public function index(AdminRepository $adminRepository): Response
    {
        $admins = $adminRepository->findAll();

        return $this->render('admin/superAdmin/index.html.twig', [
            'admins' => $admins,
        ]);
    }

    #[Route('/new', name: 'admin_manager_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AdminRepository $adminRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $admin = new Admin();
        $form = $this->createForm(AdminType::class, $admin);
        $form->get('roles')->setData('ROLE_ADMIN');
        $form->handleRequest($request);

        $errors = [];

        $email = $form->get('email')->getData();
        if ($email && $adminRepository->findOneBy(['email' => $email])) {
            $form->get('email')->addError(new \Symfony\Component\Form\FormError(
                'Este correo electrónico ya está registrado.'
            ));
        }

        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $selectedRole = $form->get('roles')->getData();

                    $admin->setRoles([$selectedRole]);

                    $hashedPassword = $passwordHasher->hashPassword(
                        $admin,
                        $form->get('password')->getData()
                    );
                    $admin->setPassword($hashedPassword);

                    $this->em->persist($admin);
                    $this->em->flush();

                    $this->addFlash('success', 'Admin creado correctamente.');
                    return $this->redirectToRoute('admin_manager_index');

                } catch (\Exception $e) {
                    $errors[] = 'Ha ocurrido un error al crear el Admin. Por favor inténtalo nuevamente.';
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        if ($isAjax) {
            return $this->render('admin/superAdmin/_new.html.twig', [
                'admin' => $admin,
                'form' => $form,
                'error_new' => !empty($errors) ? $errors[0] : null,
            ]);
        }

        return $this->redirectToRoute("admin_manager_index");
    }

    #[Route('/{id}/show', name: 'admin_manager_show', methods: ['GET'])]
    public function show(Admin $admin, Request $request): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_manager_index');
        }

        return $this->render('admin/superAdmin/_show.html.twig', [
            'admin' => $admin,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_manager_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Admin $admin, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, AdminRepository $adminRepository): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return $this->redirectToRoute('admin_manager_index');
        }

        $currentPassword = $admin->getPassword();
        $originalEmail = $admin->getEmail();
        $originalRoles = $admin->getRoles();
        $adminId = $admin->getId();
        $form = $this->createForm(AdminType::class, $admin);

        $mainRole = 'ROLE_ADMIN';
        foreach ($originalRoles as $role) {
            $mainRole = $role;
            break;
        }

        $form->get('roles')->setData($mainRole);

        $isSelfEditing = ($this->getUser() && $this->getUser()->getId() === $admin->getId());

        if ($request->isMethod('POST')) {
            $postData = $request->request->all();
            $adminData = $postData['admin'] ?? [];

            if (isset($adminData['password']) && empty($adminData['password'])) {
                $request->request->set(
                    'admin',
                    array_merge($adminData, ['password' => $currentPassword])
                );
            }
        }

        $form->handleRequest($request);
        $error_edit = null;

        if ($form->isSubmitted()) {
            $emailToCheck = $form->get('email')->getData();
            $duplicateEmail = $adminRepository->createQueryBuilder('u')
                ->where('u.email = :email')
                ->andWhere('u.id != :id')
                ->setParameter('email', $emailToCheck)
                ->setParameter('id', $adminId)
                ->getQuery()
                ->getOneOrNullResult();

            if ($duplicateEmail) {
                $form->get('email')->addError(new \Symfony\Component\Form\FormError(
                    'Este correo electrónico ya está registrado por otro usuario.'
                ));
                $error_edit = 'El correo electrónico ya está en uso por otro usuario.';
            } elseif ($form->isValid()) {
                try {
                    if (method_exists($admin, 'setUpdatedAt')) {
                        $admin->setUpdatedAt(new \DateTime());
                    }

                    $criticalChanges = false;
                    $submittedPassword = $form->get('password')->getData();
                    $submittedEmail = $form->get('email')->getData();
                    $submittedRole = $form->get('roles')->getData();

                    if ($originalEmail !== $submittedEmail ||
                        ($submittedPassword !== $currentPassword && !empty($submittedPassword)) ||
                        !in_array($submittedRole, $originalRoles)) {
                        $criticalChanges = true;
                    }

                    if ($submittedPassword !== $currentPassword && !empty($submittedPassword)) {
                        $admin->setPassword(
                            $passwordHasher->hashPassword($admin, $submittedPassword)
                        );
                    }

                    $admin->setRoles([$submittedRole]);

                    $em->flush();

                    if ($request->isXmlHttpRequest()) {
                        if ($isSelfEditing && $criticalChanges) {
                            return new JsonResponse([
                                'success' => true,
                                'selfEdited' => true,
                                'criticalChanges' => true,
                                'message' => 'Has actualizado información crítica. Por favor, inicia sesión nuevamente.'
                            ]);
                        } elseif ($isSelfEditing) {
                            return new JsonResponse([
                                'success' => true,
                                'selfEdited' => true,
                                'criticalChanges' => false,
                                'message' => 'Tu cuenta ha sido actualizada correctamente.'
                            ]);
                        }
                        return new Response('', Response::HTTP_OK);
                    }

                    if ($isSelfEditing && $criticalChanges) {
                        $this->addFlash('info', 'Has actualizado información crítica. Por favor, inicia sesión nuevamente.');
                        return $this->redirectToRoute('admin_logout');
                    } else {
                        $this->addFlash('success', 'Usuario actualizado correctamente.');
                        return $this->redirectToRoute('admin_manager_index');
                    }

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

        $admin->setPassword('');

        return $this->render('admin/superAdmin/_edit.html.twig', [
            'admin' => $admin,
            'form' => $form->createView(),
            'error_edit' => $error_edit,
            'isSelfEditing' => $isSelfEditing
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_manager_delete', methods: ['POST'])]
    public function delete(Request $request, Admin $admin): Response
    {
        if ($this->isCsrfTokenValid('delete' . $admin->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($admin);
                $this->em->flush();
                $this->addFlash('success', 'Admin eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo eliminar el Admin. Error: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_manager_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/set-status', name: 'admin_manager_set_status', methods: ['GET', 'POST'])]
    public function setStatus(Admin $admin): Response
    {
        $admin->setIsActive(!$admin->isActive());
        $admin->setUpdatedAt(new \DateTime());

        $this->em->flush();

        $status = $admin->isActive() ? 'activado' : 'desactivado';
        $this->addFlash('success', 'Admin ' . $status . ' correctamente.');

        return $this->redirectToRoute('admin_manager_index');
    }

    #[Route('/excel', name: 'admin_manager_excel', methods: ['GET', 'POST'])]
    public function generateExcel(AdminRepository $adminRepository): Response
    {
        $adminsArray = $adminRepository->findAll();
        $admins = [];

        for ($i = 0; $i < count($adminsArray); $i++) {
            $admins[$i]["ID"] = $adminsArray[$i]->getId();
            $admins[$i]["Nombre"] = $adminsArray[$i]->getName();
            $admins[$i]["Apellidos"] = $adminsArray[$i]->getLastname();
            $admins[$i]["Email"] = $adminsArray[$i]->getEmail();
            $roles = $adminsArray[$i]->getRoles();
            $mainRole = 'ROLE_ADMIN';
            foreach ($roles as $role) {
                $mainRole = $role;
                break;
            }

            $roleLabels = [
                'ROLE_ADMIN' => 'Administrador',
                'ROLE_SUPERADMIN' => 'Súper Administrador'
            ];

            $admins[$i]["Rol"] = $roleLabels[$mainRole] ?? $mainRole;
            $admins[$i]["Fecha Creación"] = $adminsArray[$i]->getCreatedAt() ? $adminsArray[$i]->getCreatedAt()->format("d/m/Y H:i:s") : '';
            $admins[$i]["Fecha Actualización"] = $adminsArray[$i]->getUpdatedAt() ? $adminsArray[$i]->getUpdatedAt()->format("d/m/Y H:i:s") : '';
            $admins[$i]["Status"] = $adminsArray[$i]->isActive() ? "Activo" : "Inactivo";
        }

        $excelManager = new ExcelManager($admins);
        $temp_file = $excelManager->generateExcel();

        return $this->file($temp_file, "Admins_" . ($_ENV['WEB_NAME'] ?? 'app') . "_" . date('Y-m-d') . ".xlsx", ResponseHeaderBag::DISPOSITION_INLINE);
    }
}