<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileFormType extends AbstractType
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
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'first_options' => [
                    'label' => 'Contraseña (dejar en blanco para no cambiarla)',
                    'constraints' => [
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}