<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Book;
use App\State\BookProvider;

/**
 * Représentation publique d'un livre du catalogue.
 *
 * Ressource exposée via un DTO : l'entité Doctrine Book reste masquée. La
 * lecture s'appuie sur Doctrine (pagination + filtre) grâce à stateOptions,
 * et BookProvider mappe les entités vers ce DTO.
 */
#[ApiResource(
    shortName: 'Book',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    provider: BookProvider::class,
    stateOptions: new Options(entityClass: Book::class),
    paginationItemsPerPage: 20,
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial'])]
final class BookOutput
{
    public int $id;

    public string $title;

    public ?string $author = null;

    public ?string $isbn = null;

    public ?string $coverUrl = null;

    public ?int $publishedYear = null;
}
