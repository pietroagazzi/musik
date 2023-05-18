<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Spotify\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * controller for the musik pages
 */
class MusikController extends AbstractController
{
	/**
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(
		private readonly EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 *
	 * @param User|null $user
	 * @param Client $spotify
	 * @return Response
	 */
	#[Route('', name: 'app_home')]
	public function index(?UserInterface $user, Client $spotify): Response
	{
		if ($user and $connection = $user->getConnection('spotify')) {
			$spotify
				->setAccessToken($connection->getToken())
				->setRefreshToken($connection->getRefresh());
		}

		return $this->render('musik/index.html.twig', [
			'spotify_api' => $spotify
		]);
	}

	#[Route('{username}', name: 'app_user', requirements: ['username' => '[a-zA-Z0-9]{4,}'], priority: -1)]
	public function user(
		string              $username,
		Client              $client,
		#[CurrentUser] User $currentUser
	): Response
	{
		/** @var UserRepository $userRepository */
		$userRepository = $this->entityManager->getRepository(User::class);

		/** @var User $user */
		if (!$user = $userRepository->findOneByUsername($username)) {
			throw $this->createNotFoundException('User not found');
		}

		if ($user === $currentUser) {
			# TODO: handle this
		}

		if ($connection = $user->getConnection('spotify')) {
			$client
				->setAccessToken($connection->getToken())
				->setRefreshToken($connection->getRefresh());
		}

		return $this->render('musik/user.html.twig', [
			'user' => $user,
			'spotify_api' => $client
		]);
	}
}
