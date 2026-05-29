<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\BookOutput;
use App\Entity\Book;

/**
 * Convertit une entité Book en DTO BookOutput.
 */
final class BookOutputMapper
{
    public function map(Book $book): BookOutput
    {
        $output = new BookOutput();
        $output->id = (int) $book->getId();
        $output->title = $book->getTitle();
        $output->author = $book->getAuthor();
        $output->isbn = $book->getIsbn();
        $output->coverUrl = $book->getCoverUrl();
        $output->publishedYear = $book->getPublishedYear();

        return $output;
    }
}
