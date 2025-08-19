<?php

namespace App\Form\Admin\SuperAdmin;

use App\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;

class AdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Introduce el nombre'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor introduce el nombre.'
                    ])
                ],
                'required' => true,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Apellidos',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Introduce el apellido'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor introduce el apellido.'
                    ])
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor introduce un email'
                    ]),
                    new Email([
                        'message' => 'El email {{ value }} no es válido'
                    ])
                ],
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rol',
                'choices' => [
                    'Administrador' => 'ROLE_ADMIN',
                    'Súper Administrador' => 'ROLE_SUPERADMIN',
                ],
                'placeholder' => 'Seleccione un rol',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor selecciona un rol.'
                    ])
                ],
                'required' => true,
                'mapped' => false,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor, introduce una contraseña.'
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres.',
                    ]),
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}