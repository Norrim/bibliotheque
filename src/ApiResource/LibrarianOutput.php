<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\LibrarianDeleteProcessor;
use App\State\LibrarianPersistProcessor;
use App\State\LibrarianProvider;

/**
 * Compte bibliothécaire (ROLE_LIBRARIAN). Gestion réservée à l'administrateur.
 */
#[ApiResource(
    shortName: 'Librarian',
    operations: [
        new GetCollection(
            uriTemplate: '/librarians',
            provider: LibrarianProvider::class,
            description: 'Lister les bibliothécaires (administrateur).',
        ),
        new Get(
            uriTemplate: '/librarians/{id}',
            requirements: ['id' => '\d+'],
            provider: LibrarianProvider::class,
        ),
        new Post(
            uriTemplate: '/librarians',
            input: AccountInput::class,
            processor: LibrarianPersistProcessor::class,
            read: false,
            description: 'Créer un compte bibliothécaire (administrateur).',
        ),
        new Put(
            uriTemplate: '/librarians/{id}',
            requirements: ['id' => '\d+'],
            input: AccountUpdateInput::class,
            processor: LibrarianPersistProcessor::class,
            read: false,
            description: 'Modifier un bibliothécaire — champs fournis uniquement (administrateur).',
        ),
        new Delete(
            uriTemplate: '/librarians/{id}',
            requirements: ['id' => '\d+'],
            processor: LibrarianDeleteProcessor::class,
            read: false,
            description: 'Supprimer un compte bibliothécaire (administrateur).',
        ),
    ],
    security: "is_granted('ROLE_ADMIN')",
)]
final class LibrarianOutput
{
    public int $id;

    public string $email;

    public string $firstName;

    public string $lastName;

    public \DateTimeImmutable $createdAt;
}
