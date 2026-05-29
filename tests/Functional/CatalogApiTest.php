<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\UserRole;

final class CatalogApiTest extends AbstractApiTestCase
{
    public function testAnonymousCannotBrowseCatalog(): void
    {
        $this->client->request('GET', '/api/books', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testMemberCanBrowseAndFilterCatalogByTitle(): void
    {
        $this->createBook('OL1W', 'Le Petit Prince');
        $this->createBook('OL2W', 'Harry Potter');
        $token = $this->tokenFor($this->createUser('member@test.local', UserRole::Member));

        $response = $this->client->request('GET', '/api/books?title=Prince', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(1, $data);
        self::assertIsArray($data[0]);
        self::assertSame('Le Petit Prince', $data[0]['title']);
    }
}
