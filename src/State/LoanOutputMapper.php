<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\LoanOutput;
use App\Entity\Loan;
use Psr\Clock\ClockInterface;

/**
 * Convertit une entité Loan en DTO LoanOutput. Le caractère « en retard » est
 * calculé au moment de la lecture grâce à l'horloge injectée.
 */
final class LoanOutputMapper
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function map(Loan $loan): LoanOutput
    {
        $output = new LoanOutput();
        $output->id = (int) $loan->getId();
        $output->bookId = (int) $loan->getBook()->getId();
        $output->bookTitle = $loan->getBook()->getTitle();
        $output->borrowedAt = $loan->getBorrowedAt();
        $output->dueAt = $loan->getDueAt();
        $output->returnRequestedAt = $loan->getReturnRequestedAt();
        $output->returnedAt = $loan->getReturnedAt();
        $output->status = $loan->getStatus()->value;
        $output->overdue = $loan->isOverdue($this->clock->now());
        $output->borrowerId = (int) $loan->getBorrower()->getId();

        return $output;
    }
}
