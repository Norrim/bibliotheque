<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\MemberDeleteProcessor;
use App\State\MemberPersistProcessor;
use App\State\MemberProvider;

/**
 * Adhérent (compte ROLE_MEMBER). Gestion réservée au bibliothécaire.
 */
#[ApiResource(
    shortName: 'Member',
    operations: [
        new GetCollection(
            uriTemplate: '/members',
            provider: MemberProvider::class,
            description: 'Lister les adhérents (bibliothécaire).',
        ),
        new Get(
            uriTemplate: '/member/{id}',
            requirements: ['id' => '\d+'],
            provider: MemberProvider::class,
        ),
        new Post(
            uriTemplate: '/members',
            input: AccountInput::class,
            processor: MemberPersistProcessor::class,
            read: false,
            description: 'Créer un adhérent (bibliothécaire).',
        ),
        new Put(
            uriTemplate: '/member/{id}',
            requirements: ['id' => '\d+'],
            input: AccountUpdateInput::class,
            processor: MemberPersistProcessor::class,
            read: false,
            description: 'Modifier un adhérent — champs fournis uniquement (bibliothécaire).',
        ),
        new Delete(
            uriTemplate: '/member/{id}',
            requirements: ['id' => '\d+'],
            processor: MemberDeleteProcessor::class,
            read: false,
            description: 'Supprimer un adhérent (bibliothécaire).',
        ),
    ],
    security: "is_granted('ROLE_LIBRARIAN')",
)]
final class MemberOutput
{
    public int $id;

    public string $email;

    public string $firstName;

    public string $lastName;

    public \DateTimeImmutable $createdAt;
}
