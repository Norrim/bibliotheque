<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\UserAccountManager;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base des processors de suppression de comptes.
 *
 * @implements ProcessorInterface<mixed, null>
 */
abstract class AbstractAccountDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        protected readonly UserAccountManager $accounts,
        protected readonly UserRepository $users,
    ) {
    }

    abstract protected function role(): UserRole;

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $id = $uriVariables['id'] ?? null;
        $user = is_numeric($id) ? $this->users->find((int) $id) : null;

        if (null === $user || !\in_array($this->role()->value, $user->getRoles(), true)) {
            throw new NotFoundHttpException('Compte introuvable.');
        }

        $this->accounts->delete($user);

        return null;
    }
}
