<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\BorrowedCountOutput;
use App\Repository\LoanRepository;

/**
 * Fournit le nombre de livres actuellement empruntés.
 *
 * @implements ProviderInterface<BorrowedCountOutput>
 */
final class BorrowedCountProvider implements ProviderInterface
{
    public function __construct(
        private readonly LoanRepository $loans,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): BorrowedCountOutput
    {
        return new BorrowedCountOutput($this->loans->countBooksOnLoan());
    }
}
