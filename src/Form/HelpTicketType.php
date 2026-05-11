<?php

namespace App\Form;

use App\Entity\HelpTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HelpTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'attr' => [
                    'placeholder' => 'What is your question about this course?',
                    'class' => 'w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:ring-purple-200 focus:ring-2',
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'High' => 'High',
                    'Medium' => 'Medium',
                    'Low' => 'Low',
                ],
                'attr' => [
                    'class' => 'w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:ring-purple-200 focus:ring-2',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Describe your issue or question clearly so the admin can help you faster.',
                    'class' => 'w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:ring-purple-200 focus:ring-2',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HelpTicket::class,
        ]);
    }
}
