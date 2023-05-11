<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use SpotifyWebAPI\SpotifyWebAPIException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event\ExceptionEvent, KernelEvents};

/**
 * Handles expired access tokens
 */
readonly class ExceptionSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private Security               $security,
		private ClientRegistry         $clientRegistry,
		private EntityManagerInterface $entityManager,
		private LoggerInterface        $logger
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
	 * handles expired access tokens
	 *
	 * @throws IdentityProviderException
	 * @throws Exception
	 * @see    https://github.com/jwilsson/spotify-web-api-php/blob/main/docs/examples/handling-errors.md
	 */
	public function handleSpotifyExpiredAccessToken(ExceptionEvent $event): void
	{
		$exception = $event->getThrowable();

		if (!$exception instanceof SpotifyWebAPIException or !$exception->hasExpiredToken()) {
			return;
		}

		// get the user's spotify connection
		/** @var User $user */
		$user = $this->security->getUser();
		$connection = $user->getServiceConnection("spotify");

		// if the user has no spotify connection, do nothing
		if (!$connection) {
			return;
		}

		// get the oauth2 client
		$client = $this->clientRegistry->getClient("spotify_main");
		$provider = $client->getOAuth2Provider();

		// get a new access token
		$newAccessToken = $provider->getAccessToken(
			'refresh_token', [
				'refresh_token' => $connection->getRefresh()
			]
		);

		// update the connection
		$connection->setToken($newAccessToken->getToken());
		$this->entityManager->persist($connection);
		$this->entityManager->flush();

		$this->logger->debug("Spotify access token refreshed");

		// continue with the original request
		$event
			->getKernel()
			->handle($event->getRequest())
			->send();

		// stop the propagation of the original request
		$event->stopPropagation();
	}
}
