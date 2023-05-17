<?php

namespace App\Controller\Provider;

use App\Entity\Connection;
use App\Entity\User;
use App\Repository\ConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
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
	 * @throws NonUniqueResultException
	 */
	#[Route('/check', name: 'check')]
	public function check(Request $request): Response
	{
		// check if the user is logged in
		if (!$user = $this->getUser() or !$user instanceof User) {
			return $this->redirectToRoute('connect_spotify_start');
		}

		// check if the user has already connected to Spotify
		if ($user->hasConnection('spotify')) {
			$this->addFlash('error', 'You have already connected to Spotify!');

			return $this->redirectToRoute('app_home');
		}

		// get the oauth client
		$client = $this->clientRegistry->getClient('spotify_main');

		try {
			/* @var Spotify $provider */
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

		// get the provider user id
		$providerUserId = $provider->getResourceOwner($accessToken)->getId();

		/** @var ConnectionRepository $connectionRepository */
		$connectionRepository = $this->entityManager->getRepository(Connection::class);

		// if the user has already connected to Spotify redirect to app_home
		if ($connectionRepository->connectionAlreadyExists('spotify', $providerUserId, $user)) {
			$this->addFlash('error', 'This account is already connected');

			// redirect to app_home
			return $this->redirectToRoute('app_home');
		}

		// create the connection
		$connection = new Connection();
		$connection
			->setProvider('spotify')
			->setToken($accessToken->getToken())
			->setRefresh($accessToken->getRefreshToken())
			->setProviderUserId($provider->getResourceOwner($accessToken)->getId())
			->setUser($user);

		// save the connection
		$this->entityManager->persist($connection);
		$this->entityManager->flush();

		$this->addFlash('success', 'Successfully connected to Spotify!');
		return $this->redirectToRoute('app_home');
	}
}
