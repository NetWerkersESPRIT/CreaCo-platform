<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Online' => 'online',
                    'Présentielle' => 'presentielle',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Cours' => 'cours',
                    'Réunion' => 'réunion',
                    'Workshop' => 'workshop',
                ],
            ])
            ->add('date')
            ->add('time')
            ->add('organizer')
            ->add('isForAllUsers')
            //->add('meetingLink', null, ['required' => false])
            //->add('platform', null, ['required' => false])
            ->add('address', null, ['required' => false])
            ->add('googleMapsLink')
            ->add('capacity')
            ->add('contact')
            ->add('targetUsers', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'username',
                'multiple' => true,
                'required' => false,
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
