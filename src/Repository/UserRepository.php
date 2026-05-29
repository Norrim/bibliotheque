<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Réhache le mot de passe au fil de l'eau si l'algorithme évolue.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne les utilisateurs possédant un rôle donné, triés par identifiant.
     *
     * Le filtrage est fait en PHP : le volume (≈ une centaine de comptes) reste
     * modeste et cela évite une requête JSON dépendante du SGBD.
     *
     * @return list<User>
     */
    public function findByRole(string $role): array
    {
        $users = array_filter(
            $this->findBy([], ['id' => 'ASC']),
            static fn (User $user): bool => \in_array($role, $user->getRoles(), true),
        );

        return array_values($users);
    }
}
