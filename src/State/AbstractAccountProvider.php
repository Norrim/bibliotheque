<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;

/**
 * Base des providers de comptes : liste les utilisateurs d'un rôle donné ou en
 * fournit un par identifiant, mappés vers le DTO de sortie correspondant.
 *
 * @implements ProviderInterface<object>
 */
abstract class AbstractAccountProvider implements ProviderInterface
{
    public function __construct(
        protected readonly UserRepository $users,
    ) {
    }

    abstract protected function role(): UserRole;

    abstract protected function toOutput(User $user): object;

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return array_map($this->toOutput(...), $this->users->findByRole($this->role()->value));
        }

        $id = $uriVariables['id'] ?? null;
        $user = is_numeric($id) ? $this->users->find((int) $id) : null;

        if (null === $user || !\in_array($this->role()->value, $user->getRoles(), true)) {
            return null;
        }

        return $this->toOutput($user);
    }
}
