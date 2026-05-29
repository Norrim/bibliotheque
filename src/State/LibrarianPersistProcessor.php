<?php

declare(strict_types=1);

namespace App\State;

use App\Domain\UserAccountManager;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;

final class LibrarianPersistProcessor extends AbstractAccountPersistProcessor
{
    public function __construct(
        UserAccountManager $accounts,
        UserRepository $users,
        private readonly UserAccountMapper $mapper,
    ) {
        parent::__construct($accounts, $users);
    }

    protected function role(): UserRole
    {
        return UserRole::Librarian;
    }

    protected function toOutput(User $user): object
    {
        return $this->mapper->toLibrarian($user);
    }
}
