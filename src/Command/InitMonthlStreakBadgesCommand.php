<?php

namespace App\Command;

use App\Entity\Badge;
use App\Repository\BadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-monthly-streak-badges',
    description: 'Initialize monthly streak badges for all 12 months'
)]
class InitMonthlStreakBadgesCommand extends Command
{
    private array $monthlyBadges = [
        1 => [
            'code' => 'janvier_streaker',
            'name' => 'Janvier Champion',
            'description' => 'Active toute une journée pendant le mois de janvier',
            'icon' => 'fa-snowflake',
            'rarity' => 'rare'
        ],
        2 => [
            'code' => 'février_streaker',
            'name' => 'Février Amoureux',
            'description' => 'Active toute une journée pendant le mois de février',
            'icon' => 'fa-heart',
            'rarity' => 'rare'
        ],
        3 => [
            'code' => 'mars_streaker',
            'name' => 'Mars Conquérant',
            'description' => 'Active toute une journée pendant le mois de mars',
            'icon' => 'fa-shield',
            'rarity' => 'rare'
        ],
        4 => [
            'code' => 'avril_streaker',
            'name' => 'Avril Ressurge',
            'description' => 'Active toute une journée pendant le mois d\'avril',
            'icon' => 'fa-leaf',
            'rarity' => 'rare'
        ],
        5 => [
            'code' => 'mai_streaker',
            'name' => 'Mai Magnifique',
            'description' => 'Active toute une journée pendant le mois de mai',
            'icon' => 'fa-flower',
            'rarity' => 'rare'
        ],
        6 => [
            'code' => 'juin_streaker',
            'name' => 'Juin Joyeux',
            'description' => 'Active toute une journée pendant le mois de juin',
            'icon' => 'fa-sun',
            'rarity' => 'rare'
        ],
        7 => [
            'code' => 'juillet_streaker',
            'name' => 'Juillet Guerrier',
            'description' => 'Active toute une journée pendant le mois de juillet',
            'icon' => 'fa-fire',
            'rarity' => 'rare'
        ],
        8 => [
            'code' => 'août_streaker',
            'name' => 'Août Aventurier',
            'description' => 'Active toute une journée pendant le mois d\'août',
            'icon' => 'fa-compass',
            'rarity' => 'rare'
        ],
        9 => [
            'code' => 'septembre_streaker',
            'name' => 'Septembre Savant',
            'description' => 'Active toute une journée pendant le mois de septembre',
            'icon' => 'fa-book',
            'rarity' => 'rare'
        ],
        10 => [
            'code' => 'octobre_streaker',
            'name' => 'Octobre Observateur',
            'description' => 'Active toute une journée pendant le mois d\'octobre',
            'icon' => 'fa-eye',
            'rarity' => 'rare'
        ],
        11 => [
            'code' => 'novembre_streaker',
            'name' => 'Novembre Noble',
            'description' => 'Active toute une journée pendant le mois de novembre',
            'icon' => 'fa-crown',
            'rarity' => 'rare'
        ],
        12 => [
            'code' => 'décembre_streaker',
            'name' => 'Décembre Déterminé',
            'description' => 'Active toute une journée pendant le mois de décembre',
            'icon' => 'fa-gift',
            'rarity' => 'epic'
        ]
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $created = 0;
        $skipped = 0;

        foreach ($this->monthlyBadges as $month => $badgeData) {
            $existing = $this->badgeRepository->findOneByCode($badgeData['code']);

            if ($existing) {
                $io->writeln("Badge '{$badgeData['name']}' already exists, skipping.");
                $skipped++;
                continue;
            }

            $badge = new Badge();
            $badge->setCode($badgeData['code']);
            $badge->setName($badgeData['name']);
            $badge->setDescription($badgeData['description']);
            $badge->setIcon($badgeData['icon']);
            $badge->setRarity($badgeData['rarity']);

            $this->entityManager->persist($badge);
            $io->writeln("Created badge: '{$badgeData['name']}'");
            $created++;
        }

        $this->entityManager->flush();

        $io->success("Initialized monthly streak badges. Created: {$created}, Skipped: {$skipped}");
        return Command::SUCCESS;
    }
}
