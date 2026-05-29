<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\OpenLibrary\OpenLibraryClient;
use App\Domain\OpenLibrary\OpenLibraryException;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Synchronise le catalogue de livres depuis OpenLibrary.
 *
 * Idempotente : chaque livre est inséré ou mis à jour selon sa clé OpenLibrary.
 * Utilisée à l'initialisation et chaque nuit (via le Scheduler).
 */
#[AsCommand(
    name: 'app:books:sync',
    description: 'Synchronise le catalogue de livres depuis OpenLibrary (upsert).',
)]
final class SyncBooksCommand extends Command
{
    public function __construct(
        private readonly OpenLibraryClient $openLibrary,
        private readonly BookRepository $books,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Sujet OpenLibrary à importer', 'fiction')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre de livres à récupérer', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $subject = (string) $input->getOption('subject');
        $limit = max(1, (int) $input->getOption('limit'));

        $io->title(\sprintf('Synchronisation OpenLibrary (sujet « %s », limite %d)', $subject, $limit));

        try {
            $items = $this->openLibrary->fetchBooksBySubject($subject, $limit);
        } catch (OpenLibraryException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            $book = $this->books->findOneByOpenLibraryKey($item->openLibraryKey);

            if (null === $book) {
                $book = new Book($item->openLibraryKey, $item->title);
                $this->entityManager->persist($book);
                ++$created;
            } else {
                $book->setTitle($item->title);
                ++$updated;
            }

            $book->setAuthor($item->author);
            $book->setIsbn($item->isbn);
            $book->setCoverUrl($item->coverUrl);
            $book->setPublishedYear($item->publishedYear);
        }

        $this->entityManager->flush();

        $io->success(\sprintf('Synchronisation terminée : %d ajout(s), %d mise(s) à jour.', $created, $updated));

        return Command::SUCCESS;
    }
}
