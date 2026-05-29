<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Initialise les données de démonstration de façon idempotente :
 *  - charge les fixtures (utilisateurs) si aucun utilisateur n'existe ;
 *  - synchronise 100 livres depuis OpenLibrary si le catalogue est vide.
 *
 * Conçue pour être appelée au démarrage des conteneurs : un simple
 * `docker compose up` fournit une API prête à l'emploi, sans écraser des
 * données existantes lors des redémarrages.
 */
#[AsCommand(
    name: 'app:demo:init',
    description: 'Initialise les données de démonstration si la base est vide (idempotent).',
)]
final class DemoInitCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly BookRepository $books,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $application = $this->getApplication();
        if (null === $application) {
            $io->error('Application console indisponible.');

            return Command::FAILURE;
        }

        if (0 === $this->users->count([]) && $application->has('doctrine:fixtures:load')) {
            $io->section('Chargement des fixtures (utilisateurs)');
            $loadFixtures = new ArrayInput(['command' => 'doctrine:fixtures:load']);
            $loadFixtures->setInteractive(false);
            $application->find('doctrine:fixtures:load')->run($loadFixtures, $output);
        } else {
            $io->writeln('Utilisateurs déjà présents : fixtures ignorées.');
        }

        if (0 === $this->books->count([])) {
            $io->section('Synchronisation du catalogue OpenLibrary');
            $sync = new ArrayInput(['command' => 'app:books:sync', '--limit' => '100']);
            $sync->setInteractive(false);
            $application->find('app:books:sync')->run($sync, $output);
        } else {
            $io->writeln('Catalogue déjà présent : synchronisation ignorée.');
        }

        $io->success('Initialisation de démonstration terminée.');

        return Command::SUCCESS;
    }
}
