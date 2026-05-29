<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Rôles applicatifs. La valeur correspond au rôle de sécurité Symfony stocké
 * sur l'utilisateur ; la hiérarchie (ADMIN > LIBRARIAN > MEMBER) est définie
 * dans config/packages/security.yaml.
 */
enum UserRole: string
{
    case Member = 'ROLE_MEMBER';
    case Librarian = 'ROLE_LIBRARIAN';
    case Admin = 'ROLE_ADMIN';
}
