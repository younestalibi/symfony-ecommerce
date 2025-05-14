<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50';
        $labelClass = 'block text-sm font-medium text-gray-700';

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('price', NumberType::class, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('quantity', NumberType::class, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
