<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\DailyPuzzleSelector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * À planifier en cron tous les jours à 00h05 Europe/Paris.
 * Idempotent : si l'énigme du jour existe déjà, retourne simplement OK.
 */
#[AsCommand(name: 'app:puzzle:select-today', description: "Tire l'énigme du jour (idempotent).")]
final class SelectTodayPuzzleCommand extends Command
{
    public function __construct(private readonly DailyPuzzleSelector $selector)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $puzzle = $this->selector->selectForToday();
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $difficulty = $puzzle->getCommune()->getDifficulty();

        $io->success(sprintf(
            'Énigme #%d du %s : %s (%s, population %d, %s)',
            $puzzle->getPuzzleNumber(),
            $puzzle->getPuzzleDate()->format('Y-m-d'),
            $puzzle->getCommune()->getName(),
            $puzzle->getCommune()->getDepartmentName(),
            $puzzle->getCommune()->getPopulation(),
            null === $difficulty ? 'n/a' : $difficulty->value,
        ));

        return Command::SUCCESS;
    }
}
