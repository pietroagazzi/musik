<?php

namespace App\Spotify;

use App\Entity\Connection;
use App\Repository\ConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use SplSubject;

readonly class TokenRefreshObserver implements \SplObserver
{
	public function __construct(
		private EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 * @throws NonUniqueResultException if the refresh token is not unique
	 */
	public function update(Session|SplSubject $subject): void
	{
		/** @var ConnectionRepository $connectionRepository */
		$connectionRepository = $this->entityManager
			->getRepository(Connection::class);

		$connection = $connectionRepository
			->findOneByRefresh($subject->getRefreshToken());

		$connection->setToken($subject->getAccessToken());
		$connection->setRefresh($subject->getRefreshToken());

		$connectionRepository->save($connection, flush: true);
	}
}