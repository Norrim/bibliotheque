<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données des utilisateurs : un administrateur, un bibliothécaire et
 * 100 adhérents. Tous partagent le mot de passe de démonstration ci-dessous.
 *
 * Les 100 livres ne sont pas chargés ici : ils proviennent d'OpenLibrary via
 * la commande `app:books:sync` (lancée après le chargement des fixtures).
 */
final class UserFixtures extends Fixture
{
    public const string DEMO_PASSWORD = 'password';
    public const int MEMBER_COUNT = 100;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(20260529); // fixtures reproductibles

        $manager->persist($this->createUser('admin@biblio.test', [UserRole::Admin->value], 'Alice', 'Admin'));
        $manager->persist($this->createUser('librarian@biblio.test', [UserRole::Librarian->value], 'Bob', 'Bibliothécaire'));

        for ($i = 1; $i <= self::MEMBER_COUNT; ++$i) {
            $manager->persist($this->createUser(
                \sprintf('member%d@biblio.test', $i),
                [UserRole::Member->value],
                $faker->firstName(),
                $faker->lastName(),
            ));
        }

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles, string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setRoles($roles)
            ->setFirstName($firstName)
            ->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));

        return $user;
    }
}
