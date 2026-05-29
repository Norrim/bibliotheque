<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base des tests fonctionnels d'API. Chaque test s'exécute dans une transaction
 * annulée à la fin (DAMA DoctrineTestBundle), garantissant l'isolation.
 */
abstract class AbstractApiTestCase extends ApiTestCase
{
    protected Client $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $email, UserRole $role): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email)
            ->setRoles([$role->value])
            ->setFirstName('Test')
            ->setLastName('User');
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createBook(string $openLibraryKey, string $title): Book
    {
        $book = new Book($title, $openLibraryKey);
        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    protected function createLoan(Book $book, User $borrower): Loan
    {
        $loan = new Loan($book, $borrower, new \DateTimeImmutable());
        $this->em->persist($loan);
        $this->em->flush();

        return $loan;
    }

    protected function tokenFor(User $user): string
    {
        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
