<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom de produit',
                    ]),
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 p-2 mb-2 border border-slate-300 p-2',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une description',
                    ]),
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 p-2 mb-2 border border-slate-300',
                    'rows' => 4,
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'constraints' => [
                    new Positive([
                        'message' => 'Le prix doit être supérieur à 0',
                    ]),
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 p-2 mb-2 border border-slate-300',
                ],
            ])
            ->add('stock', NumberType::class, [
                'label' => 'Stock',
                'constraints' => [
                    new Positive([
                        'message' => 'Le stock doit être supérieur à 0',
                    ]),
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 p-2 mb-2 border border-slate-300',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (jpg, jpeg, png)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (jpg, jpeg, png)',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'bg-slate-300 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out mt-4']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}