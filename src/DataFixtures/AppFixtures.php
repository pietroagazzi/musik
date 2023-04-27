<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        // generate users using username provider
        foreach ($this->usernameProvider() as $username) {
            $manager->persist($this->createUser($username));
        }

        $manager->flush();
    }

    /**
     * @return Generator
     */
    public function usernameProvider(): Generator
    {
        yield 'fabien';
        yield 'kevin';
    }

    /**
     * @param string $username
     * @return User
     */
    public function createUser(string $username): User
    {
        $user = new User();
        $user
            ->setUsername($username)
            ->setEmail("$username@email.com")
            ->setPassword($this->passwordHasher->hashPassword($user, $username));

        return $user;
    }
}
