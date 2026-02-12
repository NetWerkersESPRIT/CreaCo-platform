<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Ressource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\File;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ressource = $options['data'] ?? null;
        $defaultNature = 'fichier';
        
        if ($ressource && $ressource->getId()) {
            if ($ressource->getContenu() && !$ressource->getUrl()) {
                $defaultNature = 'texte';
            }
        }

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la ressource',
                'attr' => ['placeholder' => 'Ex: Support de cours PDF']
            ])
            ->add('nature', ChoiceType::class, [
                'label' => 'Type de ressource',
                'choices' => [
                    'Fichier (PDF, Image, Video)' => 'fichier',
                    'Texte' => 'texte',
                ],
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => $defaultNature,
            ])
            ->add('fichier', FileType::class, [
                'label' => 'Fichier (PDF, Image, Video)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/jpeg',
                            'image/png',
                            'video/mp4',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier valide (PDF, JPG, PNG, MP4)',
                    ])
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu texte',
                'required' => false,
                'attr' => ['rows' => 10, 'placeholder' => 'Écrivez votre texte ici...']
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'label' => 'Cours associé'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ressource::class,
        ]);
    }
}
