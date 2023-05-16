<?php

namespace App\Controller;

use App\Entity\User;
use App\Spotify\Session;
use App\Spotify\SpotifyWebApi;
use App\Spotify\TokenRefreshObserver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * controller for the musik pages
 */
class MusikController extends AbstractController
{
	public function __construct(
		private readonly EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 *
	 * @param Session $session
	 * @param User|null $user
	 * @return Response
	 */
	#[Route('', name: 'app_home')]
	public function index(Session $session, ?UserInterface $user): Response
	{
		if ($user) {
			$connection = $user->getServiceConnection('spotify');
			$session->attach(new TokenRefreshObserver($this->entityManager));

			if ($connection) {
				$session->setAccessToken($connection->getToken());
				$session->setRefreshToken($connection->getRefresh());

				$api = new SpotifyWebApi([
					'auto_refresh' => true,
					'auto_retry' => true,
					'return_assoc' => true,
				], $session);

				$api->setAccessToken($connection->getToken());
			}
		}

		return $this->render('musik/index.html.twig');
	}

	#[Route('{username}', name: 'app_user', requirements: ['username' => '[a-zA-Z0-9]{4,}'], priority: -1)]
	public function user(string $username, Session $session): Response
	{
		return new Response();
	}
}
