<?php

namespace App\EventListener;

use App\Entity\Connection;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(event: InteractiveLoginEvent::class, method: 'onSecurityInteractiveLogin')]
readonly class UserAuthenticationListener
{
    public function __construct(
        private Security               $security,
        private ClientRegistry         $clientRegistry,
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * sends a flash message if the user is not verified when logging in
     * @param InteractiveLoginEvent $event
     * @return void
     * @throws Exception
     */
    public function onSecurityInteractiveLogin(
        InteractiveLoginEvent $event
    ): void
    {
        $this->addFlashIfNotVerified($event);
        $this->refreshOAuthToken($event);
    }

    private function addFlashIfNotVerified(
        InteractiveLoginEvent $event
    ): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User && !$user->isVerified()) {
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

    /**
     * On login, refresh the OAuth token for each connection
     * @throws IdentityProviderException
     */
    private function refreshOAuthToken(
        InteractiveLoginEvent $event
    ): void
    {
        /** @var ?User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $connections = $user->getConnections();

        foreach ($connections as $connection) {
            if ($connection instanceof Connection) {
                $client = $this->clientRegistry->getClient("{$connection->getService()}_main");

                $provider = $client->getOAuth2Provider();

                $newAccessToken = $provider->getAccessToken('refresh_token', [
                    'refresh_token' => $connection->getRefresh()
                ]);

                $connection->setToken($newAccessToken->getToken());

                $this->entityManager->persist($connection);
                $this->entityManager->flush();
            }
        }
    }
}