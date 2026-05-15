<?php

namespace App\Command;

use App\Entity\Idea;
use App\Entity\Mission;
use App\Entity\Task;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-content',
    description: 'Seeds 7 ideas, 7 missions, and 14+ tasks.',
)]
class SeedContentCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Fetch a user to assign things to
        $userRepo = $this->entityManager->getRepository(Users::class);
        $creator = $userRepo->findOneBy(['username' => 'Creator_1']);
        
        if (!$creator) {
            // Fallback to any user
            $creator = $userRepo->findOneBy([]);
        }

        if (!$creator) {
            $io->error('No users found in database. Run user seeding first.');
            return Command::FAILURE;
        }

        $categories = ['Metaverse', 'AI', 'Blockchain', 'Social', 'Gaming', 'Education', 'Productivity'];
        $states = ['TODO', 'DOING', 'DONE'];

        for ($i = 1; $i <= 7; $i++) {
            // 1. Create Idea
            $idea = new Idea();
            $idea->setTitle("Visionary Concept #$i: " . $categories[$i-1] . " Expansion");
            $idea->setDescription("An innovative approach to integrating " . $categories[$i-1] . " into the CreaCo ecosystem for better collaboration.");
            $idea->setCategory($categories[$i-1]);
            $idea->setCreator($creator); // Fixed field name
            $idea->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($idea);

            // 2. Create Mission
            $mission = new Mission();
            $mission->setTitle("Mission: Implement " . $categories[$i-1] . " Strategy");
            $mission->setDescription("A high-stakes mission to deploy the " . $categories[$i-1] . " components as outlined in the initial concept.");
            $mission->setState($states[array_rand($states)]);
            $mission->setImplementIdea($idea);
            $mission->setAssignedBy($creator); // Use the field found in Mission.php
            $mission->setCreatedAt(new \DateTimeImmutable());
            $mission->setMissionDate(new \DateTime('+'.($i*2).' days'));
            $this->entityManager->persist($mission);

            // 3. Create at least 2 Tasks per Mission
            for ($j = 1; $j <= 2; $j++) {
                $task = new Task();
                $task->setTitle("Task $j for Mission $i: Development Phase");
                $task->setDescription("Execute the technical requirements for the " . $categories[$i-1] . " implementation.");
                $task->setState($states[array_rand($states)]);
                $task->setBelongTo($mission);
                $task->setIssuedBy($creator);
                $task->setAssumedBy($creator); // For simplicity, assign to same user
                $task->setTimeLimit(new \DateTime('+'.($i*2 + $j).' days'));
                $task->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($task);
            }
        }

        $this->entityManager->flush();

        $io->success('Successfully seeded 7 Ideas, 7 Missions, and 14 Tasks.');

        return Command::SUCCESS;
    }
}
