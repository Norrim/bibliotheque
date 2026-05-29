<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class LoanNotActiveException extends \RuntimeException implements DomainException
{
    public function __construct()
    {
        parent::__construct("Cet emprunt n'est pas en cours : il a déjà été rendu ou restitué.");
    }
}
