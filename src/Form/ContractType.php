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
                'label' => 'Titre du contrat',
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant (HT)',
                'currency' => 'TND',
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Conditions (Objet)',
                'attr' => ['rows' => 5],
            ])
            ->add('paymentSchedule', TextareaType::class, [
                'label' => 'Modalités de paiement',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('confidentialityClause', TextareaType::class, [
                'label' => 'Clause de confidentialité',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('cancellationTerms', TextareaType::class, [
                'label' => 'Conditions d\'annulation',
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
