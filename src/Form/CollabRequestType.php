<?php

namespace App\Form;

use App\Entity\CollabRequest;
use App\Entity\Collaborator;
use App\Entity\Users;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollabRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('collaborator', EntityType::class, [
                'class' => Collaborator::class,
                'choice_label' => 'companyName',
                'label' => 'Concerned partner',
                'placeholder' => 'Select a partner',
                'required' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'Request title'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Detailed description',
                'attr' => ['rows' => 8]
            ])
            ->add('budget', MoneyType::class, [
                'label' => 'Budget',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Start date',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'End date',
                'widget' => 'single_text',
            ])
            ->add('deliverables', TextareaType::class, [
                'label' => 'Expected deliverables',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Describe the expected deliverables'
                ]
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label' => 'Payment terms',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'e.g.: 50% at start, 50% on delivery'
                ]
            ])
            ->add('revisor', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'username',
                'label' => 'Select a manager for revision',
                'placeholder' => 'Choose a manager',
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->orWhere('u.role = :role_alt')
                        ->setParameter('role', 'manager')
                        ->setParameter('role_alt', 'ROLE_MANAGER')
                        ->orderBy('u.username', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CollabRequest::class,
            // Désactiver le contrôle de saisie côté client (navigateur)
            'attr' => [
                'novalidate' => 'novalidate',
            ]
        ]);
    }
}
