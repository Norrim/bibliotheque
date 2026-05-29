<?php

declare(strict_types=1);

namespace App\Domain\OpenLibrary;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client de l'API publique OpenLibrary. Récupère une liste de livres par sujet
 * et la normalise en objets BookData prêts à être persistés.
 */
final class OpenLibraryClient
{
    public function __construct(
        #[Autowire(service: 'openlibrary')]
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Récupère des livres d'un sujet donné.
     *
     * @return list<BookData>
     *
     * @throws OpenLibraryException en cas d'erreur réseau ou de réponse invalide
     */
    public function fetchBooksBySubject(string $subject = 'fiction', int $limit = 100): array
    {
        try {
            $response = $this->client->request('GET', \sprintf('/subjects/%s.json', $subject), [
                'query' => ['limit' => $limit],
            ]);

            /** @var array{works?: array<int, array<string, mixed>>} $data */
            $data = $response->toArray();
        } catch (HttpExceptionInterface $e) {
            $this->logger->error('Échec de récupération des livres OpenLibrary.', ['exception' => $e]);

            throw new OpenLibraryException('Impossible de récupérer les livres depuis OpenLibrary.', previous: $e);
        }

        $books = [];
        foreach ($data['works'] ?? [] as $work) {
            $book = $this->mapWork($work);
            if (null !== $book) {
                $books[] = $book;
            }
        }

        return $books;
    }

    /**
     * @param array<string, mixed> $work
     */
    private function mapWork(array $work): ?BookData
    {
        $key = isset($work['key']) && \is_string($work['key']) ? basename($work['key']) : null;
        $title = isset($work['title']) && \is_string($work['title']) ? $work['title'] : null;

        if (null === $key || '' === $key || null === $title) {
            return null;
        }

        $author = null;
        $authors = $work['authors'] ?? null;
        if (\is_array($authors) && isset($authors[0]) && \is_array($authors[0])
            && isset($authors[0]['name']) && \is_string($authors[0]['name'])) {
            $author = $authors[0]['name'];
        }

        $coverUrl = null;
        if (isset($work['cover_id']) && \is_int($work['cover_id'])) {
            $coverUrl = \sprintf('https://covers.openlibrary.org/b/id/%d-L.jpg', $work['cover_id']);
        }

        $publishedYear = isset($work['first_publish_year']) && \is_int($work['first_publish_year'])
            ? $work['first_publish_year']
            : null;

        return new BookData(
            openLibraryKey: $key,
            title: $title,
            author: $author,
            isbn: null,
            coverUrl: $coverUrl,
            publishedYear: $publishedYear,
        );
    }
}
