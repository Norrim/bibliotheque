<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AccountInput;
use App\ApiResource\AccountUpdateInput;
use App\Domain\Exception\DomainException;
use App\Domain\UserAccountManager;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base des processors de comptes : création (POST, AccountInput) et mise à jour
 * partielle (PATCH, AccountUpdateInput).
 *
 * @implements ProcessorInterface<AccountInput|AccountUpdateInput, object>
 */
abstract class AbstractAccountPersistProcessor implements ProcessorInterface
{
    public function __construct(
        protected readonly UserAccountManager $accounts,
        protected readonly UserRepository $users,
    ) {
    }

    abstract protected function role(): UserRole;

    abstract protected function toOutput(User $user): object;

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $id = $uriVariables['id'] ?? null;

        if (is_numeric($id)) {
            \assert($data instanceof AccountUpdateInput);
            $user = $this->users->find((int) $id);
            if (null === $user || !\in_array($this->role()->value, $user->getRoles(), true)) {
                throw new NotFoundHttpException('Compte introuvable.');
            }
            $this->accounts->update($user, $data->firstName, $data->lastName, $data->password);

            return $this->toOutput($user);
        }

        \assert($data instanceof AccountInput);

        try {
            $user = $this->accounts->create(
                (string) $data->email,
                (string) $data->password,
                (string) $data->firstName,
                (string) $data->lastName,
                $this->role(),
            );
        } catch (DomainException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return $this->toOutput($user);
    }
}
