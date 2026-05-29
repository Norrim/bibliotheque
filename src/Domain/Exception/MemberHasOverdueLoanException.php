<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class MemberHasOverdueLoanException extends \RuntimeException implements DomainException
{
    public function __construct()
    {
        parent::__construct("Vous avez un emprunt en retard : impossible d'emprunter un nouveau livre.");
    }
}
