<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\LoanOutput;
use App\Entity\User;
use App\Repository\LoanRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Fournit la liste des emprunts de l'adhérent authentifié.
 *
 * @implements ProviderInterface<LoanOutput>
 */
final class MyLoansProvider implements ProviderInterface
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Security $security,
        private readonly LoanOutputMapper $mapper,
    ) {
    }

    /**
     * @return list<LoanOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $loans = $this->loans->findBy(['borrower' => $user], ['borrowedAt' => 'DESC']);

        return array_values(array_map($this->mapper->map(...), $loans));
    }
}
