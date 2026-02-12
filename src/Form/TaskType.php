<?php

namespace App\Form;

use App\Entity\Mission;
use App\Entity\Task;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length(['min' => 3, 'max' => 255]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire']),
                ],
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'À faire' => 'todo',
                    'En cours' => 'in_progress',
                    'Terminé' => 'completed',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L\'état est obligatoire']),
                ],
            ])
            ->add('timeTlimit', DateTimeType::class, [
                'label' => 'Date limite',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('belongTo', EntityType::class, [
                'class' => Mission::class,
                'choice_label' => 'title',
                'label' => 'Mission associée',
                'placeholder' => 'Choisir une mission',
                'required' => false,
            ])
            ->add('assumedBy', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'username',
                'label' => 'Assigné à',
                'placeholder' => 'Choisir un éditeur',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
