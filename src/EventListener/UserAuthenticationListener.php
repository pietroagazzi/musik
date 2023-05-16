<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(event: InteractiveLoginEvent::class, method: 'onSecurityInteractiveLogin')]
readonly class UserAuthenticationListener
{
	/**
	 * @param Security $security
	 */
	public function __construct(
		private Security $security,
	)
	{
	}

	/**
	 * sends a flash message if the user is not verified when logging in
	 *
	 * @param InteractiveLoginEvent $event
	 * @return void
	 */
	public function onSecurityInteractiveLogin(
		InteractiveLoginEvent $event
	): void
	{
		$user = $this->security->getUser();

		if ($user instanceof User && !$user->isVerified()) {
			/**
			 * @var FlashBagInterface $flashBag
			 */
			$flashBag = $event
				->getRequest()
				->getSession()
				->getBag('flashes');

			$flashBag->add(
				'warning',
				'Your account is not verified. Please check your email for a verification link.'
			);
		}
	}
}
