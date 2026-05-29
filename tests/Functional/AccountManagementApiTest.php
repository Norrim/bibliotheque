<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\UserRole;

final class AccountManagementApiTest extends AbstractApiTestCase
{
    public function testLibrarianCanCreateAndListMembers(): void
    {
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $this->client->request('POST', '/api/members', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['email' => 'new.member@test.local', 'password' => 'password', 'firstName' => 'New', 'lastName' => 'Member'],
        ]);
        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['email' => 'new.member@test.local', 'firstName' => 'New']);

        $this->client->request('GET', '/api/members', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
    }

    public function testMemberCannotManageMembers(): void
    {
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));

        $this->client->request('GET', '/api/members', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));
        $payload = [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['email' => 'dup@test.local', 'password' => 'password', 'firstName' => 'A', 'lastName' => 'B'],
        ];

        $this->client->request('POST', '/api/members', $payload);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/members', $payload);
        self::assertResponseStatusCodeSame(409);
    }

    public function testLibrarianCanUpdateAndDeleteMember(): void
    {
        $member = $this->createUser('target@test.local', UserRole::Member);
        $token = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $this->client->request('PUT', '/api/members/'.$member->getId(), [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['firstName' => 'Modifié'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['firstName' => 'Modifié']);

        $this->client->request('DELETE', '/api/members/'.$member->getId(), [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);
        self::assertResponseStatusCodeSame(204);
    }

    public function testAdminCanCreateLibrarianButLibrarianCannot(): void
    {
        $adminToken = $this->tokenFor($this->createUser('admin@test.local', UserRole::Admin));
        $librarianToken = $this->tokenFor($this->createUser('lib@test.local', UserRole::Librarian));

        $payload = static fn (string $email): array => [
            'headers' => ['Accept' => 'application/json'],
            'json' => ['email' => $email, 'password' => 'password', 'firstName' => 'Lib', 'lastName' => 'Rarian'],
        ];

        // Un bibliothécaire ne peut pas gérer les comptes bibliothécaires.
        $this->client->request('POST', '/api/librarians', ['auth_bearer' => $librarianToken] + $payload('a@test.local'));
        self::assertResponseStatusCodeSame(403);

        // L'administrateur le peut.
        $this->client->request('POST', '/api/librarians', ['auth_bearer' => $adminToken] + $payload('b@test.local'));
        self::assertResponseStatusCodeSame(201);
    }
}
