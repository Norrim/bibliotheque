<?php

declare(strict_types=1);

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données d'entrée pour modifier un compte (mise à jour partielle). L'email
 * n'est pas modifiable ; le mot de passe n'est changé que s'il est fourni.
 */
final class AccountUpdateInput
{
    public ?string $firstName = null;

    public ?string $lastName = null;

    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.')]
    public ?string $password = null;
}
