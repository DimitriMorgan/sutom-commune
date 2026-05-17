<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GuessPathStep;
use App\Dto\PuzzleGuessOutput;
use App\Dto\PuzzleSolution;
use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use App\Entity\GuessEvent;
use App\Repository\CommuneRepository;
use App\Repository\GuessEventRepository;
use App\ValueObject\Coordinates;
use Doctrine\ORM\EntityManagerInterface;

final class GuessOrchestrator
{
    public const MAX_ATTEMPTS = 6;

    public function __construct(
        private readonly CommuneRepository $communeRepo,
        private readonly GuessEventRepository $guessRepo,
        private readonly DistanceCalculator $distanceCalculator,
        private readonly ProximityTierResolver $tierResolver,
        private readonly HintProvider $hintProvider,
        private readonly ShareGridGenerator $gridGenerator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws InvalidGuessException si la requête est incohérente ou si la commune n'existe pas
     */
    public function submit(DailyPuzzle $puzzle, string $sessionHash, int $attemptIndex, string $guessCodeInsee): PuzzleGuessOutput
    {
        $history = $this->guessRepo->findForSession($puzzle, $sessionHash);

        if (\count($history) >= self::MAX_ATTEMPTS) {
            throw new InvalidGuessException('Partie déjà terminée pour cette session.');
        }
        foreach ($history as $h) {
            if ($h->isCorrect()) {
                throw new InvalidGuessException('Partie déjà gagnée pour cette session.');
            }
        }
        $expectedAttempt = \count($history) + 1;
        if ($attemptIndex !== $expectedAttempt) {
            throw new InvalidGuessException(sprintf(
                'attemptIndex incohérent : attendu %d, reçu %d.',
                $expectedAttempt,
                $attemptIndex,
            ));
        }

        $guess = $this->communeRepo->findOneByCodeInsee($guessCodeInsee);
        if (null === $guess) {
            throw new InvalidGuessException(sprintf('Commune INSEE %s introuvable.', $guessCodeInsee));
        }

        $target = $puzzle->getCommune();
        $isCorrect = $guess->getCodeInsee() === $target->getCodeInsee();

        $distAndBearing = $this->distanceCalculator->compute(
            new Coordinates((float) $guess->getLatitude(), (float) $guess->getLongitude()),
            new Coordinates((float) $target->getLatitude(), (float) $target->getLongitude()),
        );
        $tier = $this->tierResolver->fromKilometers($distAndBearing->distanceKm);

        $event = new GuessEvent();
        $event
            ->setPuzzle($puzzle)
            ->setSessionHash($sessionHash)
            ->setAttemptIndex($attemptIndex)
            ->setGuessCommune($guess)
            ->setIsCorrect($isCorrect)
            ->setDistanceKm($distAndBearing->distanceKm);
        $this->em->persist($event);
        $this->em->flush();

        $allEvents = [...$history, $event];
        $isLastAttempt = $attemptIndex >= self::MAX_ATTEMPTS;
        $gameOver = $isCorrect || $isLastAttempt;

        if ($gameOver) {
            return $this->buildGameOver($puzzle, $target, $allEvents, $isCorrect);
        }

        $nextHint = $this->hintProvider->hintForAttempt($target, $attemptIndex);

        return new PuzzleGuessOutput(
            isCorrect: false,
            gameOver: false,
            distanceKm: $distAndBearing->distanceKm,
            bearing: $distAndBearing->bearingLabel,
            bearingDegrees: $distAndBearing->bearingDegrees,
            proximityTier: $tier,
            nextHint: $nextHint,
            attemptsRemaining: self::MAX_ATTEMPTS - $attemptIndex,
        );
    }

    /**
     * @param list<GuessEvent> $events
     */
    private function buildGameOver(DailyPuzzle $puzzle, Commune $target, array $events, bool $won): PuzzleGuessOutput
    {
        $path = [];
        $tilesForGrid = [];
        foreach ($events as $event) {
            $commune = $event->getGuessCommune();
            if (null === $commune) {
                $tilesForGrid[] = ['tier' => 'red', 'bearing' => null, 'isCorrect' => false, 'isRecognized' => false];

                continue;
            }
            $isCorrect = $event->isCorrect();
            $distance = $event->getDistanceKm() ?? 0;
            $tier = $this->tierResolver->fromKilometers($distance);
            $bearingCompute = $this->distanceCalculator->compute(
                new Coordinates((float) $commune->getLatitude(), (float) $commune->getLongitude()),
                new Coordinates((float) $target->getLatitude(), (float) $target->getLongitude()),
            );

            $path[] = new GuessPathStep(
                codeInsee: $commune->getCodeInsee(),
                name: $commune->getName(),
                latitude: (float) $commune->getLatitude(),
                longitude: (float) $commune->getLongitude(),
                distanceKm: $distance,
                bearing: $bearingCompute->bearingLabel,
                bearingDegrees: $bearingCompute->bearingDegrees,
                proximityTier: $tier,
                isCorrect: $isCorrect,
            );

            $tilesForGrid[] = [
                'tier' => $tier,
                'bearing' => $bearingCompute->bearingLabel,
                'isCorrect' => $isCorrect,
                'isRecognized' => true,
            ];
        }

        $shareGrid = $this->gridGenerator->generate($puzzle->getPuzzleNumber(), $tilesForGrid, $won);

        return new PuzzleGuessOutput(
            isCorrect: $won,
            gameOver: true,
            solution: new PuzzleSolution(
                codeInsee: $target->getCodeInsee(),
                name: $target->getName(),
                departmentName: $target->getDepartmentName(),
                departmentCode: $target->getDepartmentCode(),
                latitude: (float) $target->getLatitude(),
                longitude: (float) $target->getLongitude(),
                population: $target->getPopulation(),
                patrimoine: $target->getPatrimoine(),
                wikipediaUrl: $target->getWikipediaUrl(),
            ),
            guessPath: $path,
            shareGrid: $shareGrid,
        );
    }
}
