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
                'label' => 'Title',
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['min' => 3, 'max' => 255]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5],
                'constraints' => [
                    new NotBlank(['message' => 'Description is required']),
                ],
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'To Do' => 'todo',
                    'In Progress' => 'in_progress',
                    'Completed' => 'completed',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Status is required']),
                ],
            ])
            ->add('timeTlimit', DateTimeType::class, [
                'label' => 'Deadline',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('belongTo', EntityType::class, [
                'class' => Mission::class,
                'choice_label' => 'title',
                'label' => 'Associated Mission',
                'placeholder' => 'Choose a mission',
                'required' => false,
            ])
            ->add('assumedBy', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'username',
                'label' => 'Assigned To',
                'placeholder' => 'Choose an editor',
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
