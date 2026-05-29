<?php

declare(strict_types=1);

namespace App\State;

use App\Enum\UserRole;

final class LibrarianDeleteProcessor extends AbstractAccountDeleteProcessor
{
    protected function role(): UserRole
    {
        return UserRole::Librarian;
    }
}
