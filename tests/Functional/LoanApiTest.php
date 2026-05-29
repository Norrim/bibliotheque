<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\UserRole;

final class LoanApiTest extends AbstractApiTestCase
{
    public function testMemberCanBorrowAnAvailableBook(): void
    {
        $book = $this->createBook('OL1W', '1984');
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));

        $this->client->request('POST', '/api/loans', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['bookId' => $book->getId()],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['status' => 'active', 'bookId' => $book->getId()]);
    }

    public function testBorrowingAnUnavailableBookIsRejected(): void
    {
        $book = $this->createBook('OL1W', '1984');
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));
        $payload = ['auth_bearer' => $token, 'headers' => ['Accept' => 'application/json'], 'json' => ['bookId' => $book->getId()]];

        $this->client->request('POST', '/api/loans', $payload);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/loans', $payload);
        self::assertResponseStatusCodeSame(409);
    }

    public function testBorrowingWithoutBookIdIsUnprocessable(): void
    {
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));

        $this->client->request('POST', '/api/loans', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testBorrowedCountIsForbiddenForMembers(): void
    {
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));

        $this->client->request('GET', '/api/loans/borrowed-count', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testBorrowedCountIsAllowedForLibrarians(): void
    {
        $token = $this->tokenFor($this->createUser('librarian@test.local', UserRole::Librarian));

        $this->client->request('GET', '/api/loans/borrowed-count', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['borrowedBooks' => 0]);
    }
}
