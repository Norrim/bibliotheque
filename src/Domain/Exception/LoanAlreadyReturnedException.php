<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class LoanAlreadyReturnedException extends \RuntimeException implements DomainException
{
    public function __construct()
    {
        parent::__construct('Cet emprunt a déjà été restitué.');
    }
}
