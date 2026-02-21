<?php

namespace App\Form;

use App\Entity\Contract;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Contract title',
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start date',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'End date',
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Amount (excl. VAT)',
                'currency' => 'TND',
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Terms (Object)',
                'attr' => ['rows' => 5],
            ])
            ->add('paymentSchedule', TextareaType::class, [
                'label' => 'Payment schedule',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('confidentialityClause', TextareaType::class, [
                'label' => 'Confidentiality clause',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('cancellationTerms', TextareaType::class, [
                'label' => 'Cancellation terms',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
        ]);
    }
}
