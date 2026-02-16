<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RejectionReasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rejectionReason', TextareaType::class, [
                'label' => 'Rejection reason',
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please provide a reason for the rejection.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas d'entité liée
            'data_class' => null,
            // Désactiver le contrôle de saisie en HTML5 (navigateur)
            'attr' => [
                'novalidate' => 'novalidate',
            ]
        ]);
    }
}
