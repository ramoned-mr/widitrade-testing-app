<?php

namespace App\Form\Admin\Product;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('asin', TextType::class, [
                'label' => 'ASIN',
                'constraints' => [
                    new NotBlank(['message' => 'El ASIN es obligatorio'])
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Título',
                'constraints' => [
                    new NotBlank(['message' => 'El título es obligatorio'])
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => false,
            ])
            ->add('brand', TextType::class, [
                'label' => 'Marca',
                'constraints' => [
                    new NotBlank(['message' => 'La marca es obligatoria'])
                ],
            ])
            ->add('manufacturer', TextType::class, [
                'label' => 'Fabricante',
                'required' => false,
            ])
            ->add('amazonUrl', UrlType::class, [
                'label' => 'URL de Amazon',
                'constraints' => [
                    new NotBlank(['message' => 'La URL de Amazon es obligatoria'])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}