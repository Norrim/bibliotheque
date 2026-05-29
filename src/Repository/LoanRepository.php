<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Enum\LoanStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * Nombre total de livres actuellement empruntés : tant qu'un retour n'a pas
     * été validé, le livre n'est pas revenu en rayon (statuts « active » et
     * « return_requested »).
     */
    public function countBooksOnLoan(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.status != :returned')
            ->setParameter('returned', LoanStatus::Returned)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre d'emprunts détenus par un adhérent (statut « active »), pour la
     * limite de 3 : rendre un livre libère immédiatement le quota.
     */
    public function countActiveForBorrower(User $borrower): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.borrower = :borrower')
            ->andWhere('l.status = :status')
            ->setParameter('borrower', $borrower)
            ->setParameter('status', LoanStatus::Active)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Indique si l'adhérent possède au moins un emprunt actif en retard.
     */
    public function hasOverdueLoans(User $borrower, \DateTimeImmutable $now): bool
    {
        $count = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.borrower = :borrower')
            ->andWhere('l.status = :status')
            ->andWhere('l.dueAt < :now')
            ->setParameter('borrower', $borrower)
            ->setParameter('status', LoanStatus::Active)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Indique si un livre est actuellement sorti (modèle à un exemplaire par
     * livre) : il reste indisponible tant que son retour n'est pas validé.
     */
    public function isBookOnLoan(Book $book): bool
    {
        $count = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.book = :book')
            ->andWhere('l.status != :returned')
            ->setParameter('book', $book)
            ->setParameter('returned', LoanStatus::Returned)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
