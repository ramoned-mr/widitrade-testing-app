<?php

namespace App\Form\User;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionType', ChoiceType::class, [
                'label' => 'Tipo de consulta',
                'choices' => [
                    'Información general' => 'general',
                    'Soporte técnico' => 'technical',
                    'Otro' => 'other',
                ],
                'placeholder' => 'Seleccione un tipo de consulta',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor seleccione un tipo de consulta.',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Mensaje',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Escribe tu mensaje aquí...',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor escribe un mensaje',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'Tu mensaje debe tener al menos {{ limit }} caracteres',
                        'maxMessage' => 'Tu mensaje no puede tener más de {{ limit }} caracteres',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}