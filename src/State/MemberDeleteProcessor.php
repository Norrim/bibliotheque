<?php

declare(strict_types=1);

namespace App\State;

use App\Enum\UserRole;

final class MemberDeleteProcessor extends AbstractAccountDeleteProcessor
{
    protected function role(): UserRole
    {
        return UserRole::Member;
    }
}
