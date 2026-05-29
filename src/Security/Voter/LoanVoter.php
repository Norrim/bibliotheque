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
 * Autorise la consultation d'un emprunt : le staff (bibliothécaire/admin) peut
 * consulter n'importe quel emprunt, un adhérent uniquement les siens.
 *
 * @extends Voter<string, LoanOutput>
 */
final class LoanVoter extends Voter
{
    public const string VIEW = 'LOAN_VIEW';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof LoanOutput;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Le staff (la hiérarchie de rôles fait que l'admin a aussi ROLE_LIBRARIAN).
        if ($this->security->isGranted('ROLE_LIBRARIAN')) {
            return true;
        }

        \assert($subject instanceof LoanOutput);

        return $subject->borrowerId === $user->getId();
    }
}
