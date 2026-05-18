<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CommuneImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:communes:import',
    description: 'Import / re-sync des communes françaises depuis geo.api.gouv.fr (idempotent, UPSERT par codeInsee).'
)]
final class ImportCommunesCommand extends Command
{
    private const ENDPOINT = 'https://geo.api.gouv.fr/communes';
    private const FIELDS = 'nom,code,codesPostaux,codeDepartement,codeRegion,population,centre';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CommuneImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Override de l\'URL source', self::ENDPOINT)
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Charger depuis un fichier JSON local au lieu du réseau');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getOption('file');
        if (\is_string($file) && '' !== $file) {
            $io->section("Lecture du fichier local : $file");
            $payloads = $this->loadFromFile($file);
        } else {
            $url = (string) $input->getOption('url');
            $io->section("Téléchargement : $url");
            $payloads = $this->fetchFromHttp($url);
        }

        if (null === $payloads) {
            $io->error('Aucune donnée chargée.');

            return Command::FAILURE;
        }

        $total = \count($payloads);
        $io->writeln(sprintf('  → %d communes à traiter', $total));

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        $summary = $this->importer->importFromIterable($payloads, static function () use ($progressBar): void {
            $progressBar->advance();
        });

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf(
            'Import terminé. %d traitées · %d créées · %d mises à jour · %d ignorées.',
            $summary->processed,
            $summary->created,
            $summary->updated,
            $summary->skipped,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchFromHttp(string $url): ?array
    {
        $response = $this->httpClient->request('GET', $url, [
            'query' => ['fields' => self::FIELDS],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 120.0,
        ]);

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $payload = $response->toArray(false);

        return array_values(array_filter($payload, 'is_array'));
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function loadFromFile(string $path): ?array
    {
        if (!is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($contents, true);
        if (!\is_array($decoded)) {
            return null;
        }

        return array_values(array_filter($decoded, 'is_array'));
    }
}
