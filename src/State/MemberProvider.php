<?php

declare(strict_types=1);

namespace App\State;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;

final class MemberProvider extends AbstractAccountProvider
{
    public function __construct(
        UserRepository $users,
        private readonly UserAccountMapper $mapper,
    ) {
        parent::__construct($users);
    }

    protected function role(): UserRole
    {
        return UserRole::Member;
    }

    protected function toOutput(User $user): object
    {
        return $this->mapper->toMember($user);
    }
}
