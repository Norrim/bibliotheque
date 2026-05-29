<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\BorrowedCountProvider;

/**
 * Nombre de livres actuellement empruntés (emprunts actifs).
 */
#[ApiResource(
    shortName: 'LoanStatistics',
    operations: [
        new Get(
            uriTemplate: '/loans/borrowed-count',
            provider: BorrowedCountProvider::class,
            security: "is_granted('ROLE_LIBRARIAN')",
            description: 'Nombre de livres actuellement empruntés.',
        ),
    ],
)]
final class BorrowedCountOutput
{
    public function __construct(
        public int $borrowedBooks = 0,
    ) {
    }
}
