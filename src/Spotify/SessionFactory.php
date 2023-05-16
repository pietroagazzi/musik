<?php

namespace App\Spotify;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Factory for creating spotify sessions
 *
 * This factory is responsible for creating spotify sessions
 * with autowired dependencies.
 *
 * @see Session
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
readonly class SessionFactory
{
	/**
	 * @param string $clientId the client id
	 * @param string $clientSecret the client secret
	 * @param EntityManagerInterface $entityManager the entity manager
	 */
	public function __construct(
		private string                 $clientId,
		private string                 $clientSecret,
		private EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 * creates a new session as a token observer client
	 *
	 * @return Session the created session
	 */
	public function asTokenObserverClient(): Session
	{
		// create a new session
		$session = $this->create();

		// attach the token refresh observer
		$session->attach(new TokenRefreshObserver($this->entityManager));

		return $session;
	}

	/**
	 * creates a new session
	 *
	 * @return Session the created session
	 */
	public function create(): Session
	{
		return new Session(
			$this->clientId,
			$this->clientSecret,
		);
	}
}