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
                'label' => 'Partenaire concerné',
                'placeholder' => 'Sélectionnez un partenaire',
                'required' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre de la demande'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => ['rows' => 8]
            ])
            ->add('budget', MoneyType::class, [
                'label' => 'Budget',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
            ])
            ->add('deliverables', TextareaType::class, [
                'label' => 'Livrables attendus',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez les livrables attendus'
                ]
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label' => 'Conditions de paiement',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'ex: 50% au démarrage, 50% à la livraison'
                ]
            ])
            ->add('revisor', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'username',
                'label' => 'Sélectionnez un manager pour révision',
                'placeholder' => 'Choisissez un manager',
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
