<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\UserRole;

final class CatalogManagementApiTest extends AbstractApiTestCase
{
    public function testLibrarianCanCreateBook(): void
    {
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $this->client->request('POST', '/api/books', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['title' => 'Nouveau Livre', 'author' => 'Moi'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['title' => 'Nouveau Livre', 'author' => 'Moi']);
    }

    public function testMemberCannotCreateBook(): void
    {
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));

        $this->client->request('POST', '/api/books', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['title' => 'Interdit'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testCreatingBookWithoutTitleIsUnprocessable(): void
    {
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $this->client->request('POST', '/api/books', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testLibrarianCanUpdateBook(): void
    {
        $book = $this->createBook('OL1W', 'Ancien titre');
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $this->client->request('PUT', '/api/books/'.$book->getId(), [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['title' => 'Titre modifié'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['title' => 'Titre modifié']);
    }

    public function testLibrarianCanDeleteBook(): void
    {
        $book = $this->createBook('OL1W', 'À supprimer');
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $this->client->request('DELETE', '/api/books/'.$book->getId(), [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(204);
    }
}
