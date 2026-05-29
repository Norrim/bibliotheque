<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\BookNotAvailableException;
use App\Domain\Exception\LoanAlreadyReturnedException;
use App\Domain\Exception\MaxActiveLoansReachedException;
use App\Domain\Exception\MemberHasOverdueLoanException;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Service de domaine portant les règles d'emprunt de la bibliothèque :
 *  - un adhérent ne peut pas dépasser MAX_ACTIVE_LOANS emprunts simultanés ;
 *  - un adhérent ayant un emprunt en retard ne peut plus emprunter ;
 *  - un livre déjà emprunté n'est pas disponible ;
 *  - la durée d'un emprunt est fixée par l'entité Loan (21 jours).
 *
 * L'horloge est injectée (Psr\Clock) pour rendre ces règles testables.
 */
final class LoanManager
{
    /**
     * Nombre maximum d'emprunts actifs simultanés par adhérent.
     */
    public const int MAX_ACTIVE_LOANS = 3;

    public function __construct(
        private readonly LoanRepository $loans,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Enregistre l'emprunt d'un livre par un adhérent après contrôle des règles.
     *
     * @throws MemberHasOverdueLoanException si l'adhérent a un emprunt en retard
     * @throws MaxActiveLoansReachedException si la limite d'emprunts est atteinte
     * @throws BookNotAvailableException      si le livre est déjà emprunté
     */
    public function borrow(User $borrower, Book $book): Loan
    {
        $now = $this->clock->now();

        if ($this->loans->hasOverdueLoans($borrower, $now)) {
            throw new MemberHasOverdueLoanException();
        }

        if ($this->loans->countActiveForBorrower($borrower) >= self::MAX_ACTIVE_LOANS) {
            throw new MaxActiveLoansReachedException(self::MAX_ACTIVE_LOANS);
        }

        if ($this->loans->hasActiveLoanForBook($book)) {
            throw new BookNotAvailableException();
        }

        $loan = new Loan($book, $borrower, $now);
        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        return $loan;
    }

    /**
     * Enregistre la restitution d'un emprunt.
     *
     * @throws LoanAlreadyReturnedException si l'emprunt a déjà été restitué
     */
    public function returnBook(Loan $loan): void
    {
        if (!$loan->isActive()) {
            throw new LoanAlreadyReturnedException();
        }

        $loan->markReturned($this->clock->now());
        $this->entityManager->flush();
    }
}
