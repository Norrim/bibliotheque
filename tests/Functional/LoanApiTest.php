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

    public function testMemberReturnsThenLibrarianValidates(): void
    {
        $member = $this->createUser('member@test.local', UserRole::Member);
        $loan = $this->createLoan($this->createBook('OL1W', '1984'), $member);
        $memberToken = $this->tokenFor($member);
        $librarianToken = $this->tokenFor($this->createUser('librarian@test.local', UserRole::Librarian));

        // Étape 1 : l'adhérent rend le livre.
        $this->client->request('POST', '/api/loan/'.$loan->getId().'/return', [
            'auth_bearer' => $memberToken,
            'headers' => ['Accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['status' => 'return_requested']);

        // Étape 2 : le bibliothécaire valide le retour.
        $this->client->request('POST', '/api/loan/'.$loan->getId().'/validate-return', [
            'auth_bearer' => $librarianToken,
            'headers' => ['Accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['status' => 'returned']);
    }

    public function testMemberCannotReturnSomeoneElsesLoan(): void
    {
        $owner = $this->createUser('owner@test.local', UserRole::Member);
        $loan = $this->createLoan($this->createBook('OL1W', '1984'), $owner);
        $otherToken = $this->tokenFor($this->createUser('intruder@test.local', UserRole::Member));

        $this->client->request('POST', '/api/loan/'.$loan->getId().'/return', [
            'auth_bearer' => $otherToken,
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testMemberCannotValidateReturn(): void
    {
        $member = $this->createUser('member@test.local', UserRole::Member);
        $loan = $this->createLoan($this->createBook('OL1W', '1984'), $member);

        $this->client->request('POST', '/api/loan/'.$loan->getId().'/validate-return', [
            'auth_bearer' => $this->tokenFor($member),
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
