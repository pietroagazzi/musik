<?php

namespace App\EventListener;

use App\Entity\Connection;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use http\Client;
use PHPUnit\Util\Exception;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles expired access tokens
 */
readonly class ExceptionSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private Security               $security,
		private ClientRegistry         $clientRegistry,
		private EntityManagerInterface $entityManager,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::EXCEPTION => [
				['handleSpotifyExpiredAccessToken', 10],
			],
		];
	}

	/**
	 * @throws OptimisticLockException
	 * @throws ORMException
	 */
	public function handleSpotifyExpiredAccessToken($event): void
	{
		$exception = $event->getThrowable();

		if (!$exception instanceof SpotifyWebAPIException or !$exception->hasExpiredToken()) {
			return;
		}

		$connection = $this->getSpotifyConnection();

		$client = $this->clientRegistry->getClient("{$connection->getService()}_main");

		$provider = $client->getOAuth2Provider();

		$newAccessToken = $provider->getAccessToken('refresh_token', [
			'refresh_token' => $connection->getRefresh()
		]);

		$connection->setToken($newAccessToken->getToken());

		$this->entityManager->persist($connection);
		$this->entityManager->flush();
	}

	protected function getSpotifyConnection(): Connection
	{
		/** @var User $user */
		$user = $this->security->getUser();

		return $user
			->getConnections()
			->filter(
				fn(Connection $connection) => $connection->getService() === 'spotify'
			)->first();
	}
}