<?php

namespace App\Form;

use App\Entity\HelpTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HelpTicketResponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adminResponse', TextareaType::class, [
                'label' => 'Admin Response',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Write your response to the content creator here.',
                    'class' => 'w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:ring-purple-200 focus:ring-2',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'Pending',
                    'Closed' => 'Closed',
                ],
                'attr' => [
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
