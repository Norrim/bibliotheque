<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class EmailAlreadyUsedException extends \RuntimeException implements DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(\sprintf('L\'email « %s » est déjà utilisé.', $email));
    }
}
