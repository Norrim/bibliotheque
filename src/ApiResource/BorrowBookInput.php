<?php

declare(strict_types=1);

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données d'entrée pour emprunter un livre. L'adhérent est déduit du JWT.
 */
final class BorrowBookInput
{
    #[Assert\NotNull(message: 'Le livre à emprunter est obligatoire.')]
    #[Assert\Positive(message: "L'identifiant du livre doit être un entier positif.")]
    public ?int $bookId = null;
}
