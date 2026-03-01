<?php

namespace App\Repository;

use App\Entity\UserStreakDay;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserStreakDay>
 */
class UserStreakDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserStreakDay::class);
    }

    public function findOneByUserAndDate(Users $user, \DateTimeInterface $date): ?UserStreakDay
    {
        $day = new \DateTime($date->format('Y-m-d'));

        return $this->findOneBy([
            'user' => $user,
            'day' => $day,
        ]);
    }

    public function countDaysInMonthForUser(Users $user, int $year, int $month): int
    {
        $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $end = (clone $start)->modify('first day of next month');

        return (int) $this->createQueryBuilder('usd')
            ->select('COUNT(usd.id)')
            ->where('usd.user = :user')
            ->andWhere('usd.day >= :start')
            ->andWhere('usd.day < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }
}
