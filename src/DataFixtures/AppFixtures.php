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

        // generate services using service provider
        foreach ($this->serviceProvider() as $data) {
            $manager->persist($this->createService($data));
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

    /**
     * @return Generator
     */
    public function serviceProvider(): Generator
    {
        yield [
            'name' => 'spotify',
            'url' => 'https://spotify.com',
            'icon_url' => 'https://developer.spotify.com/images/guidelines/design/icon2@2x.png'
        ];
        yield [
            'name' => 'apple music',
            'url' => 'https://music.apple.com',
            'icon_url' => 'https://developer.apple.com/design/human-interface-guidelines/macos/images/app-icon-realistic-materials_2x.png'
        ];
    }

    public function createService(array $data): Service
    {
        $service = new Service();
        $service
            ->setName($data['name'])
            ->setUrl($data['url'])
            ->setIconUrl($data['icon_url']);

        return $service;
    }
}
