<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use SpotifyWebAPI\SpotifyWebAPIException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\RuntimeError;

/**
 * Provides methods to handle exceptions
 *
 * @see https://symfony.com/doc/current/event_dispatcher.html
 * @see https://symfony.com/doc/current/reference/events.html
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
	public function __construct(
		protected Security               $security,
		protected RouterInterface        $router,
		protected EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::EXCEPTION => [
				['spotifyClientException', 0],
			],
		];
	}

	/**
	 * disconnects the user if the spotify oauth is invalid
	 * @param ExceptionEvent $event
	 */
	public function spotifyClientException(ExceptionEvent $event): void
	{
		$exception = $event->getThrowable();

		// if the exception is thrown by twig get the previous exception
		if ($exception instanceof RuntimeError) {
			$exception = $exception->getPrevious();
		}

		if ($exception instanceof SpotifyWebAPIException && $exception->getMessage() === 'Bad OAuth request') {
			/** @var User|null $user */
			$user = $this->security->getUser();

			if ($user && $connection = $user->getConnection('spotify')) {
				$this->entityManager->remove($connection);
				$this->entityManager->flush();

				/** @var FlashBagInterface $flashBag */
				$flashBag = $event
					->getRequest()
					->getSession()
					->getBag('flashes');

				$flashBag->add('error', 'An error occurred while connecting to Spotify. Please try again.');
			}

			$event->setResponse(new RedirectResponse($this->router->generate('app_home')));
		}
	}
}