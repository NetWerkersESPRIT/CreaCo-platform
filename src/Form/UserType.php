<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Username',  
                    'class' => 'mb-4 text-sm focus:shadow-soft-primary-outline leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding py-2 px-3 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:bg-white focus:text-gray-700 focus:outline-none focus:transition-shadow'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter a username',
                    ]),
                    new Assert\Length([
                        'min' => 4,
                        'minMessage' => 'Votre username doit contenir minimum {{ limit }} characters',
                        'max' => 50,
                    ])
                ],
                
                ])

            ->add('email' , TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Email',  
                    'class' => 'mb-4 text-sm focus:shadow-soft-primary-outline leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding py-2 px-3 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:bg-white focus:text-gray-700 focus:outline-none focus:transition-shadow'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter an email address',
                    ]),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
                ])

            ->add('password', PasswordType::class, [
                'label' => false,
                'required' => !$options['optional_password'],
                'attr' => [
                    'placeholder' => $options['optional_password'] ? 'Leave blank to keep current password' : 'Password',  
                    'class' => 'mb-4 text-sm focus:shadow-soft-primary-outline leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding py-2 px-3 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:bg-white focus:text-gray-700 focus:outline-none focus:transition-shadow'
                ],
                'constraints' => array_merge(
                    $options['optional_password'] ? [] : [new Assert\NotBlank(['message' => 'Please enter a password',])],
                    [
                        new Assert\Length([
                            'min' => 6,
                            'minMessage' => 'Your password must be at least {{ limit }} characters long',
                            'max' => 4096,
                        ]),
                        new Assert\Regex([
                            'pattern' => '/^(?=.*[A-Z])(?=.*\d).+$/',
                            'message' => 'Your password must contain at least one uppercase letter and one number',
                        ]),
                    ]
                ),
            ])

            ->add('numtel' , TextType::class, [
                'label' => false,
                'required' => !$options['optional_numtel'],
                'attr' => [
                    'placeholder' => 'Phone Number',  
                    'class' => 'mb-4 text-sm focus:shadow-soft-primary-outline leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding py-2 px-3 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:bg-white focus:text-gray-700 focus:outline-none focus:transition-shadow'
                ],
                
                'constraints' => array_merge(
                    $options['optional_numtel'] ? [] : [new Assert\NotBlank(['message' => 'Please enter a phone number',])],
                    [new Assert\Regex([
                        'pattern' => '/^\d{8}$/',
                        'message' => 'Please enter a valid 8-digit phone number',
                    ])]
                ),
                ])
        ;

        if ($options['include_role']) {
            $builder->add('role', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Member' => 'ROLE_MEMBER',
                    'Manager' => 'ROLE_MANAGER',
                ],
                'attr' => [
                    'class' => 'mb-4 text-sm focus:shadow-soft-primary-outline leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding py-2 px-3 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:bg-white focus:text-gray-700 focus:outline-none focus:transition-shadow'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please select a role',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
            'include_role' => false,
            'optional_numtel' => true,
            'optional_password' => false,
        ]);
    }
}
