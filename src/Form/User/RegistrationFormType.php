<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingresa tu nombre',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Tu nombre debe tener al menos {{ limit }} caracteres',
                        'max' => 50,
                        'maxMessage' => 'Tu nombre no puede tener más de {{ limit }} caracteres',
                    ]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Apellido',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingresa tu apellido',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Tu apellido debe tener al menos {{ limit }} caracteres',
                        'max' => 50,
                        'maxMessage' => 'Tu apellido no puede tener más de {{ limit }} caracteres',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingresa tu email',
                    ]),
                    new Email([
                        'message' => 'El email {{ value }} no es un email válido',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'first_options' => [
                    'label' => 'Contraseña',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Por favor ingresa una contraseña',
                        ]),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Tu contraseña debe tener al menos {{ limit }} caracteres',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Repetir contraseña',
                ],
                'invalid_message' => 'Las contraseñas deben coincidir.',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'He leído y acepto las condiciones de uso y privacidad',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Debes aceptar nuestros términos y condiciones',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}