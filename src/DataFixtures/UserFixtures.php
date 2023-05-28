<?php

namespace App\DataFixtures;

use App\Entity\Follow;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use function array_rand;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
	public const REFERENCE_PREFIX = 'user';

	public function __construct(
		private readonly UserPasswordHasherInterface $passwordHasher
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public static function getGroups(): array
	{
		return ['user'];
	}

	/**
	 * @inheritDoc
	 */
	public function load(ObjectManager $manager): void
	{
		$this->loadUser($manager);
		$this->loadFollows($manager);
	}

	public function loadUser(ObjectManager $manager): void
	{
		// generate users using username provider
		foreach ($this->userDataProvider() as [$username, $email, $password]) {
			$user = new User;

			$user
				->setUsername($username)
				->setEmail($email)
				->setPassword($this->passwordHasher->hashPassword($user, $password));

			$manager->persist($user);

			$this->setReference(self::REFERENCE_PREFIX . ':' . $username, $user);
		}

		$manager->flush();
	}

	/**
	 * @return Generator<array{username: string, email: string, password: string}>
	 */
	public function userDataProvider(): Generator
	{
		// [username, email, password]
		yield ['fabien', 'fabien@gmail.com', 'fabien'];
		yield ['kevin', 'kevin@example.org', 'kevin'];
		yield ['john', 'john47@hotmail.it', 'john'];
	}

	public function loadFollows(ObjectManager $manager): void
	{
		/**
		 * @var User $follower
		 * @var User $followed
		 */
		foreach ($this->followDataProvider() as [$follower, $followed]) {
			$follow = (new Follow)
				->setFollower($follower)
				->setFollowed($followed);

			$manager->persist($follow);
		}

		$manager->flush();
	}

	public function followDataProvider(): Generator
	{
		$follows = [];
		$users = [];

		foreach ($this->userDataProvider() as [$username, ,]) {
			$users[] = $this->getReference(self::REFERENCE_PREFIX . ':' . $username);
		}

		// if users are less than 2, we can't generate follows
		if (($usersCount = count($users)) < 2) {
			return;
		}

		for ($i = 0; $i < $usersCount / 2; ++$i) {
			$follow = array_rand($users, 2);

			$follow = [$users[$follow[0]], $users[$follow[1]]];

			// check if follows already exists in $follows
			if (in_array($follow, $follows, true)) {
				$i--;
				continue;
			}

			$follows[] = $follow;

			yield $follow;
		}
	}
}