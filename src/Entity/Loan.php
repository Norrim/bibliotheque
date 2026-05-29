<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LoanStatus;
use App\Repository\LoanRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Emprunt d'un livre par un adhérent.
 *
 * La durée d'un emprunt est fixée à 21 jours : l'échéance (dueAt) est calculée
 * à la création à partir de la date d'emprunt. Le retour est matérialisé par
 * returnedAt et le passage du statut à RETURNED.
 */
#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loan')]
class Loan
{
    /**
     * Durée d'un emprunt, en jours.
     */
    public const int DURATION_DAYS = 21;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $borrower;

    #[ORM\Column]
    private \DateTimeImmutable $borrowedAt;

    #[ORM\Column]
    private \DateTimeImmutable $dueAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(enumType: LoanStatus::class)]
    private LoanStatus $status;

    public function __construct(Book $book, User $borrower, \DateTimeImmutable $borrowedAt)
    {
        $this->book = $book;
        $this->borrower = $borrower;
        $this->borrowedAt = $borrowedAt;
        $this->dueAt = $borrowedAt->modify(\sprintf('+%d days', self::DURATION_DAYS));
        $this->status = LoanStatus::Active;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getBorrower(): User
    {
        return $this->borrower;
    }

    public function getBorrowedAt(): \DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function getDueAt(): \DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function getStatus(): LoanStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return LoanStatus::Active === $this->status;
    }

    /**
     * Un emprunt est en retard s'il est encore actif et que son échéance est dépassée.
     */
    public function isOverdue(\DateTimeImmutable $now): bool
    {
        return $this->isActive() && $this->dueAt < $now;
    }

    /**
     * Matérialise le retour du livre.
     */
    public function markReturned(\DateTimeImmutable $returnedAt): void
    {
        $this->returnedAt = $returnedAt;
        $this->status = LoanStatus::Returned;
    }
}
