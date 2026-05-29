<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\EmailAlreadyUsedException;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service de domaine pour la gestion des comptes utilisateurs (adhérents et
 * bibliothécaires) : création, mise à jour et suppression, avec hachage du mot
 * de passe et contrôle d'unicité de l'email.
 */
final class UserAccountManager
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws EmailAlreadyUsedException si l'email est déjà pris
     */
    public function create(string $email, string $plainPassword, string $firstName, string $lastName, UserRole $role): User
    {
        if (null !== $this->users->findOneBy(['email' => $email])) {
            throw new EmailAlreadyUsedException($email);
        }

        $user = new User();
        $user->setEmail($email)
            ->setRoles([$role->value])
            ->setFirstName($firstName)
            ->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Met à jour les champs fournis (mise à jour partielle). L'email n'est pas
     * modifiable.
     */
    public function update(User $user, ?string $firstName, ?string $lastName, ?string $plainPassword): User
    {
        if (null !== $firstName) {
            $user->setFirstName($firstName);
        }
        if (null !== $lastName) {
            $user->setLastName($lastName);
        }
        if (null !== $plainPassword && '' !== $plainPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $this->entityManager->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
