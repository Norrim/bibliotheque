<?php

declare(strict_types=1);

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données d'entrée pour créer ou modifier un livre (gestion par le bibliothécaire).
 */
final class BookInput
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 255)]
    public ?string $author = null;

    #[Assert\Length(max: 20)]
    public ?string $isbn = null;

    #[Assert\Url(message: "L'URL de couverture n'est pas valide.")]
    #[Assert\Length(max: 1024)]
    public ?string $coverUrl = null;

    #[Assert\Range(min: 0, max: 2100)]
    public ?int $publishedYear = null;
}
