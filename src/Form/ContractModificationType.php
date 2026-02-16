<?php

namespace App\Form;

use App\Entity\Contract;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractModificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Contract amount',
                'currency' => 'EUR',
            ])
            ->add('paymentSchedule', TextareaType::class, [
                'label' => 'Payment schedule',
                'required' => false,
                'attr' => ['rows' => 3]
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'General terms',
                'required' => false,
                'attr' => ['rows' => 8]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            // Désactiver le contrôle de saisie côté client (navigateur)
            'attr' => [
                'novalidate' => 'novalidate',
            ]
        ]);
    }
}
