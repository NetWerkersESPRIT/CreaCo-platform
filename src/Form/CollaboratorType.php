<?php

namespace App\Form;

use App\Entity\Collaborator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CollaboratorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name'
            ])
            ->add('companyName', TextType::class, [
                'label' => "Company Name"
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address'
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Address',
                'required' => false,
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website',
                'required' => false,
            ])
            ->add('domain', TextType::class, [
                'label' => 'Domain',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g.: Marketing, Tech, Finance'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5
                ]
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo (Image file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collaborator::class,
            // Desactiver le contrôle de saisie en HTML5 (navigateur)
            'attr' => [
                'novalidate' => 'novalidate',
            ]
        ]);
    }
}
