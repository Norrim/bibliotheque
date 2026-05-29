<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\BorrowProcessor;
use App\State\LoanItemProvider;
use App\State\MyLoansProvider;
use App\State\ReturnLoanProcessor;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Représentation publique d'un emprunt. Ressource exposée via un DTO ;
 * l'entité Loan reste masquée.
 */
#[ApiResource(
    shortName: 'Loan',
    operations: [
        new GetCollection(
            uriTemplate: '/loans/me',
            provider: MyLoansProvider::class,
            security: "is_granted('ROLE_MEMBER')",
            description: 'Liste des emprunts de l\'adhérent authentifié.',
        ),
        new Get(
            uriTemplate: '/loans/{id}',
            requirements: ['id' => '\d+'],
            provider: LoanItemProvider::class,
            security: "is_granted('LOAN_VIEW', object)",
            securityMessage: 'Vous ne pouvez consulter que vos propres emprunts.',
        ),
        new Post(
            uriTemplate: '/loans',
            input: BorrowBookInput::class,
            processor: BorrowProcessor::class,
            security: "is_granted('ROLE_MEMBER')",
            description: 'Emprunter un livre (adhérent authentifié).',
        ),
        new Post(
            uriTemplate: '/loans/{id}/return',
            status: 200,
            requirements: ['id' => '\d+'],
            read: false,
            deserialize: false,
            processor: ReturnLoanProcessor::class,
            security: "is_granted('ROLE_LIBRARIAN')",
            description: 'Valider le retour d\'un emprunt (bibliothécaire).',
        ),
    ],
)]
final class LoanOutput
{
    public int $id;

    public int $bookId;

    public string $bookTitle;

    public \DateTimeImmutable $borrowedAt;

    public \DateTimeImmutable $dueAt;

    public ?\DateTimeImmutable $returnedAt = null;

    public string $status;

    public bool $overdue = false;

    /**
     * Identifiant de l'emprunteur, utilisé par LoanVoter pour l'autorisation.
     * Non sérialisé dans la réponse.
     */
    #[Ignore]
    public int $borrowerId = 0;
}
