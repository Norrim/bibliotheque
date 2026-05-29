<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\BorrowBookInput;
use App\ApiResource\LoanOutput;
use App\Domain\Exception\DomainException;
use App\Domain\LoanManager;
use App\Entity\User;
use App\Repository\BookRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Traite l'emprunt d'un livre : résout le livre et l'adhérent courant, applique
 * les règles métier via LoanManager et renvoie le DTO de l'emprunt créé.
 *
 * @implements ProcessorInterface<BorrowBookInput, LoanOutput>
 */
final class BorrowProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly LoanManager $loanManager,
        private readonly Security $security,
        private readonly LoanOutputMapper $mapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LoanOutput
    {
        \assert($data instanceof BorrowBookInput);

        $book = null !== $data->bookId ? $this->books->find($data->bookId) : null;
        if (null === $book) {
            throw new UnprocessableEntityHttpException('Livre introuvable.');
        }

        $user = $this->security->getUser();
        \assert($user instanceof User);

        try {
            $loan = $this->loanManager->borrow($user, $book);
        } catch (DomainException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return $this->mapper->map($loan);
    }
}
