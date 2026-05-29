<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\ApiResource\LoanOutput;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Règles d'accès sur un emprunt :
 *  - LOAN_VIEW : le staff (bibliothécaire/admin) consulte n'importe quel emprunt,
 *    un adhérent uniquement les siens ;
 *  - LOAN_RETURN : seul le propriétaire de l'emprunt peut le rendre.
 *
 * @extends Voter<string, LoanOutput>
 */
final class LoanVoter extends Voter
{
    public const string VIEW = 'LOAN_VIEW';
    public const string RETURN = 'LOAN_RETURN';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::RETURN], true) && $subject instanceof LoanOutput;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        \assert($subject instanceof LoanOutput);
        $isOwner = $subject->borrowerId === $user->getId();

        return match ($attribute) {
            // Rendre un livre : action réservée à l'adhérent propriétaire.
            self::RETURN => $isOwner,
            // Consulter : le staff voit tout (l'admin hérite de ROLE_LIBRARIAN), sinon le propriétaire.
            self::VIEW => $this->security->isGranted('ROLE_LIBRARIAN') || $isOwner,
            default => false,
        };
    }
}
