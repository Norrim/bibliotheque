<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\LoanOutput;
use App\Repository\LoanRepository;

/**
 * Fournit un emprunt par son identifiant, mappé en DTO.
 *
 * @implements ProviderInterface<LoanOutput>
 */
final class LoanItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly LoanOutputMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?LoanOutput
    {
        $loan = $this->loans->find((int) ($uriVariables['id'] ?? 0));

        return null !== $loan ? $this->mapper->map($loan) : null;
    }
}
