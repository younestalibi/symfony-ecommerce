<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50';
        $labelClass = 'block text-sm font-medium text-gray-700';
        $checkboxClass = 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded';

        $builder
            ->add('line1', null, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('line2', null, [
                'required' => false,
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('city', null, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('country', null, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('zipCode', null, [
                'attr' => ['class' => $fieldClass],
                'label_attr' => ['class' => $labelClass],
            ])
            ->add('isDefault', CheckboxType::class, [
                'required' => false,
                'attr' => ['class' => $checkboxClass],
                'label_attr' => ['class' => 'ml-2 text-sm text-gray-700'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
