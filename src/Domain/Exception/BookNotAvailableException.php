<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class BookNotAvailableException extends \RuntimeException implements DomainException
{
    public function __construct()
    {
        parent::__construct("Ce livre n'est pas disponible à l'emprunt.");
    }
}
