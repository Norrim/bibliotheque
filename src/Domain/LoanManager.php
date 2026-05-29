<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\BookNotAvailableException;
use App\Domain\Exception\LoanNotActiveException;
use App\Domain\Exception\MaxActiveLoansReachedException;
use App\Domain\Exception\MemberHasOverdueLoanException;
use App\Domain\Exception\ReturnNotAwaitingValidationException;
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
     * @throws MemberHasOverdueLoanException  si l'adhérent a un emprunt en retard
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

        if ($this->loans->isBookOnLoan($book)) {
            throw new BookNotAvailableException();
        }

        $loan = new Loan($book, $borrower, $now);
        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        return $loan;
    }

    /**
     * Rendre un livre (action de l'adhérent) : l'emprunt passe en attente de
     * validation. Le contrôle de propriété est assuré en amont (LoanVoter).
     *
     * @throws LoanNotActiveException si l'emprunt n'est pas en cours
     */
    public function requestReturn(Loan $loan): void
    {
        if (!$loan->isActive()) {
            throw new LoanNotActiveException();
        }

        $loan->requestReturn($this->clock->now());
        $this->entityManager->flush();
    }

    /**
     * Valider un retour (action du bibliothécaire) : le livre redevient disponible.
     *
     * @throws ReturnNotAwaitingValidationException si aucun retour n'est en attente
     */
    public function validateReturn(Loan $loan): void
    {
        if (!$loan->isReturnRequested()) {
            throw new ReturnNotAwaitingValidationException();
        }

        $loan->validateReturn($this->clock->now());
        $this->entityManager->flush();
    }
}
