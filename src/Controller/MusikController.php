<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function dump;

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
	 * @return Response
	 */
	#[Route('', name: 'app_home')]
	public function index(): Response
	{
		return $this->render('musik/index.html.twig');
	}

	#[Route('{username}', name: 'app_user', requirements: ['username' => '[a-zA-Z0-9]{4,}'], priority: -1)]
	public function user(string $username, Session $session): Response
	{
		/** @var UserRepository $userRepository */
		$userRepository = $this->entityManager->getRepository(User::class);
		$user = $userRepository->findOneBy(['username' => $username]);

		if (!$user) {
			throw $this->createNotFoundException('User not found');
		}

		$connection = $user->getServiceConnection('spotify');

		// build spotify api
		$session->setAccessToken($connection->getToken());
		$session->setRefreshToken($connection->getRefresh());
		$api = new SpotifyWebAPI([
			'auto_refresh' => true,
		], $session);

		// update token if changed
		if ($session->getAccessToken() !== $connection->getToken()) {
			$connection->setToken($session->getAccessToken());
			$connection->setRefresh($session->getRefreshToken());

			$this->entityManager->persist($connection);
			$this->entityManager->flush();
		}

		return $this->render('musik/user.html.twig', [
			'user' => $user,
		]);
	}
}
