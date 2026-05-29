<?php

declare(strict_types=1);

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données d'entrée pour créer un compte (adhérent ou bibliothécaire).
 */
final class AccountInput
{
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.')]
    public ?string $password = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    public ?string $lastName = null;
}
