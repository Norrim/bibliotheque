<?php

declare(strict_types=1);

namespace App\Domain\OpenLibrary;

/**
 * Données d'un livre telles que renvoyées par OpenLibrary, normalisées.
 */
final readonly class BookData
{
    public function __construct(
        public string $openLibraryKey,
        public string $title,
        public ?string $author = null,
        public ?string $isbn = null,
        public ?string $coverUrl = null,
        public ?int $publishedYear = null,
    ) {
    }
}
