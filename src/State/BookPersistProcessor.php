<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\BookInput;
use App\ApiResource\BookOutput;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Crée (POST) ou remplace (PUT) un livre du catalogue. Réservé au bibliothécaire.
 *
 * @implements ProcessorInterface<BookInput, BookOutput>
 */
final class BookPersistProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly EntityManagerInterface $entityManager,
        private readonly BookOutputMapper $mapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): BookOutput
    {
        \assert($data instanceof BookInput);
        \assert(null !== $data->title);

        $id = $uriVariables['id'] ?? null;

        if (is_numeric($id)) {
            $book = $this->books->find((int) $id);
            if (null === $book) {
                throw new NotFoundHttpException('Livre introuvable.');
            }
            $book->setTitle($data->title);
        } else {
            $book = new Book($data->title);
            $this->entityManager->persist($book);
        }

        $book->setAuthor($data->author);
        $book->setIsbn($data->isbn);
        $book->setCoverUrl($data->coverUrl);
        $book->setPublishedYear($data->publishedYear);

        $this->entityManager->flush();

        return $this->mapper->map($book);
    }
}
