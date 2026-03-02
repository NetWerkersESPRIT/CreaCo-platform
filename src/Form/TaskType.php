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
            ->add('timeLimit', DateTimeType::class, [
                'label' => 'Deadline',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => false,
            ])
            ->add('belongTo', EntityType::class, [
                'class' => Mission::class,
                'choice_label' => 'title',
                'label' => 'Associated Mission',
                'placeholder' => 'Choose a mission',
                'required' => false,
                'query_builder' => function (\App\Repository\MissionRepository $mr) use ($options) {
                    if ($options['isAdmin']) {
                        return $mr->createQueryBuilder('m')->orderBy('m.id', 'DESC');
                    }

                    $groupIds = $options['groupIds'];
                    $user = $options['currentUser'];

                    $qb = $mr->createQueryBuilder('m')
                        ->innerJoin('m.assignedBy', 'u')
                        ->leftJoin('u.groups', 'ug')
                        ->leftJoin('App\Entity\Group', 'uo', 'WITH', 'uo.owner = u');

                    $condition = 'u.id = :userId';
                    if (!empty($groupIds)) {
                        $condition .= ' OR ug.id IN (:groupIds) OR uo.id IN (:groupIds)';
                        $qb->setParameter('groupIds', $groupIds);
                    }

                    return $qb->where($condition)
                        ->setParameter('userId', $user ? $user->getId() : null)
                        ->orderBy('m.id', 'DESC');
                },
            ])
            ->add('assumedBy', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'username',
                'label' => 'Assigned To',
                'placeholder' => 'Choose an editor',
                'required' => false,
                'query_builder' => function (\App\Repository\UsersRepository $ur) use ($options) {
                    if ($options['isAdmin']) {
                        return $ur->createQueryBuilder('u')
                            ->where('u.role = :role')
                            ->setParameter('role', 'ROLE_MEMBER')
                            ->orderBy('u.username', 'ASC');
                    }

                    $groupIds = $options['groupIds'];
                    $user = $options['currentUser'];

                    $qb = $ur->createQueryBuilder('u')
                        ->leftJoin('u.groups', 'ug')
                        ->leftJoin('App\Entity\Group', 'uo', 'WITH', 'uo.owner = u');

                    $condition = '(u.id = :userId';
                    if (!empty($groupIds)) {
                        $condition .= ' OR ug.id IN (:groupIds) OR uo.id IN (:groupIds)';
                        $qb->setParameter('groupIds', $groupIds);
                    }
                    $condition .= ') AND u.role = :role';

                    return $qb->where($condition)
                        ->setParameter('userId', $user ? $user->getId() : null)
                        ->setParameter('role', 'ROLE_MEMBER')
                        ->orderBy('u.username', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'currentUser' => null,
            'groupIds' => [],
            'isAdmin' => false,
            'userRole' => null, // Keeping for B/C if needed elsewhere
        ]);
    }
}
