<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class MaxActiveLoansReachedException extends \RuntimeException implements DomainException
{
    public function __construct(int $max)
    {
        parent::__construct(\sprintf('Limite de %d emprunts simultanés atteinte.', $max));
    }
}
