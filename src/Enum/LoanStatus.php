<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statut d'un emprunt. Le caractère « en retard » n'est pas un statut stocké :
 * il se déduit dynamiquement d'un emprunt actif dont l'échéance est dépassée
 * (voir Loan::isOverdue()).
 */
enum LoanStatus: string
{
    case Active = 'active';
    case Returned = 'returned';
}
