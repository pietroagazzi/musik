<?php

namespace App\Controller\Service;

use App\Entity\Connection;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Kerox\OAuth2\Client\Provider\Spotify;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * controller for spotify oauth
 */
#[Route('/connect/spotify', name: 'connect_spotify_')]
class SpotifyController extends AbstractController
{
	/**
	 * @param ClientRegistry $clientRegistry
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(
		private readonly ClientRegistry         $clientRegistry,
		private readonly EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 * redirects to spotify oauth
	 *
	 * @return Response
	 */
	#[Route('/', name: 'start', methods: ['GET'])]
	public function index(): Response
	{
		// if the user is not logged in, redirect to home
		if (!$this->getUser() or !$this->getUser() instanceof User) {
			return $this->redirectToRoute('app_home');
		}

		// redirect to spotify oauth
		return $this->clientRegistry
			->getClient('spotify_main')
			->redirect(
				[
					'user-read-email',
					'user-read-private',
					'playlist-read-private',
					'playlist-read-collaborative',
					'playlist-modify-private',
					'playlist-modify-public',
					'user-top-read'
				]
			);
	}

	/**
	 * checks the spotify oauth response
	 *
	 * @param Request $request
	 * @return Response
	 */
	#[Route('/check', name: 'check')]
	public function check(Request $request): Response
	{
		// check if the user is logged in
		if (!$user = $this->getUser() or !$user instanceof User) {
			return $this->redirectToRoute('connect_spotify_start');
		}

		// check if the user has already connected to Spotify
		if ($user->hasServiceConnection('spotify')) {
			$this->addFlash('error', 'You have already connected to Spotify!');

			return $this->redirectToRoute('app_home');
		}

		// get the oauth client
		$client = $this->clientRegistry->getClient('spotify_main');

		try {
			/**
			 * @var Spotify $provider
			 */
			$provider = $client->getOAuth2Provider();

			// get the access token
			$accessToken = $provider->getAccessToken(
				'authorization_code', [
					'code' => $request->query->get('code'),
				]
			);
		} catch (Exception $e) {
			$this->addFlash('error', $e->getMessage());
			return $this->redirectToRoute('app_home');
		}

		// create the connection
		$connection = new Connection();
		$connection
			->setService('spotify')
			->setToken($accessToken->getToken())
			->setRefresh($accessToken->getRefreshToken())
			->setUser($user);

		// save the connection
		$this->entityManager->persist($connection);
		$this->entityManager->flush();

		$this->addFlash('success', 'Successfully connected to Spotify!');
		return $this->redirectToRoute('app_home');
	}
}
