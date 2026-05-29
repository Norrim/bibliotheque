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
    /** Emprunt en cours : le livre est détenu par l'adhérent. */
    case Active = 'active';

    /** L'adhérent a rendu le livre ; en attente de validation par le bibliothécaire. */
    case ReturnRequested = 'return_requested';

    /** Retour validé par le bibliothécaire ; le livre est de nouveau disponible. */
    case Returned = 'returned';
}
