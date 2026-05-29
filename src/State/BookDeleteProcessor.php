<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Supprime un livre du catalogue. Réservé au bibliothécaire.
 *
 * @implements ProcessorInterface<mixed, null>
 */
final class BookDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $id = $uriVariables['id'] ?? null;
        $book = is_numeric($id) ? $this->books->find((int) $id) : null;
        if (null === $book) {
            throw new NotFoundHttpException('Livre introuvable.');
        }

        $this->entityManager->remove($book);
        $this->entityManager->flush();

        return null;
    }
}
