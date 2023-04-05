<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(event: InteractiveLoginEvent::class, method: 'onSecurityInteractiveLogin')]
class UserAuthenticationListener
{
    public function __construct(
        private readonly Security $security,
    )
    {
    }

    /**
     * sends a flash message if the user is not verified when logging in
     * @param InteractiveLoginEvent $event
     * @return void
     */
    public function onSecurityInteractiveLogin(
        InteractiveLoginEvent $event
    ): void
    {
        $user = $this->security->getUser();

        if ($user instanceof UserInterface && !$user->isVerified()) {
            /** @var FlashBagInterface $flashBag */
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