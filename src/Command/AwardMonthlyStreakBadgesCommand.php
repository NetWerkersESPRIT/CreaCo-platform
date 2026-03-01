<?php

namespace App\Command;

use App\Service\GamificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:gamification:award-monthly-streak',
    description: 'Award monthly streak badges to users who read every day of a month.'
)]
class AwardMonthlyStreakBadgesCommand extends Command
{
    public function __construct(private GamificationService $gamification)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Year for badge', null)
            ->addOption('month', null, InputOption::VALUE_REQUIRED, 'Month (1-12) for badge', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist awards');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $year = $input->getOption('year');
        $month = $input->getOption('month');
        $dry = $input->getOption('dry-run');

        if (!$year || !$month) {
            $dt = new \DateTime('first day of last month');
            $year = (int)$dt->format('Y');
            $month = (int)$dt->format('m');
        }

        $output->writeln(sprintf('Checking streaks for %04d-%02d', $year, $month));

        if ($dry) {
            $output->writeln('Dry run enabled; no badges will be created.');
        }

        $awarded = $this->gamification->awardMonthlyStreakBadges($year, $month);
        $output->writeln(sprintf('Users awarded: %d', count($awarded)));
        if (!$dry && count($awarded) > 0) {
            $output->writeln(implode(', ', $awarded));
        }

        return Command::SUCCESS;
    }
}
