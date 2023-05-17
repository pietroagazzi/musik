<?php

namespace App\Spotify;

use App\Entity\Connection;
use App\Repository\ConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use SplSubject;

/**
 * Observer for spotify token refresh
 *
 * @see https://www.php.net/manual/en/class.splobserver.php
 * @see https://en.wikipedia.org/wiki/Observer_pattern
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
readonly class TokenRefreshObserver implements \SplObserver
{
	public function __construct(
		private EntityManagerInterface $entityManager
	)
	{
	}

	public function update(Session|SplSubject $subject): void
	{
		/** @var ConnectionRepository $connectionRepository */
		$connectionRepository = $this->entityManager
			->getRepository(Connection::class);

		$providerUserId = (new Client())
			->setAccessToken($subject->getAccessToken())
			->getUserId();

		$connection = $connectionRepository
			->findOneBy(['provider_user_id' => $providerUserId]);

		$connection->setToken($subject->getAccessToken());
		$connection->setRefresh($subject->getRefreshToken());

		$connectionRepository->save($connection, flush: true);
	}
}