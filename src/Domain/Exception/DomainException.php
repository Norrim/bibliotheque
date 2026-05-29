<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Marqueur des exceptions métier. Permet de les intercepter au niveau de la
 * couche API pour les traduire en réponses HTTP (409 Conflict) avec un message
 * exploitable par le client.
 */
interface DomainException extends \Throwable
{
}
