<?php

namespace App\Form;

use App\Entity\Idea;
use App\Entity\Mission;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class MissionType extends AbstractType
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
                    'New' => 'new',
                    'In Progress' => 'in_progress',
                    'Completed' => 'completed',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Status is required']),
                ],
            ])
            ->add('implementIdea', EntityType::class, [
                'class' => Idea::class,
                'choice_label' => 'title',
                'label' => 'Associated Idea',
                'placeholder' => 'Choose an idea',
                'constraints' => [
                    new NotBlank(['message' => 'Associated idea is required']),
                ],
            ])
            ->add('missionDate', null, [
                'label' => 'Mission Deadline',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Mission deadline is required']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
        ]);
    }
}
