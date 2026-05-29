<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\OpenLibrary;

use App\Domain\OpenLibrary\OpenLibraryClient;
use App\Domain\OpenLibrary\OpenLibraryException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenLibraryClientTest extends TestCase
{
    public function testItMapsAndNormalizesWorks(): void
    {
        $payload = [
            'works' => [
                [
                    'key' => '/works/OL45804W',
                    'title' => 'Fantastic Mr Fox',
                    'authors' => [['name' => 'Roald Dahl']],
                    'cover_id' => 12345,
                    'first_publish_year' => 1970,
                ],
                // Sans clé ni titre : doit être ignoré.
                ['authors' => [['name' => 'Anonymous']]],
            ],
        ];

        $client = new OpenLibraryClient(
            new MockHttpClient(new MockResponse((string) json_encode($payload))),
            new NullLogger(),
        );

        $books = $client->fetchBooksBySubject('fiction', 50);

        self::assertCount(1, $books);
        $book = $books[0];
        self::assertSame('OL45804W', $book->openLibraryKey);
        self::assertSame('Fantastic Mr Fox', $book->title);
        self::assertSame('Roald Dahl', $book->author);
        self::assertSame('https://covers.openlibrary.org/b/id/12345-L.jpg', $book->coverUrl);
        self::assertSame(1970, $book->publishedYear);
    }

    public function testItThrowsOnServerError(): void
    {
        $client = new OpenLibraryClient(
            new MockHttpClient(new MockResponse('', ['http_code' => 500])),
            new NullLogger(),
        );

        $this->expectException(OpenLibraryException::class);

        $client->fetchBooksBySubject();
    }
}
