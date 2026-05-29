<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ReturnNotAwaitingValidationException extends \RuntimeException implements DomainException
{
    public function __construct()
    {
        parent::__construct('Aucun retour en attente de validation pour cet emprunt.');
    }
}
