<?php

namespace App\Controller\Api\Action;

use App\Entity\Follow;
use App\Entity\User;
use App\Repository\FollowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;


#[Route('/api/action', name: 'api_action_')]
class ApiActionController extends AbstractController
{
	public function __construct(
		private readonly EntityManagerInterface $entityManager
	)
	{
	}

	/**
	 * @param int $user_id
	 * @param User|null $user
	 * @return Response
	 */
	#[Route('/user/{user_id}/follow', name: 'follow', requirements: ['user_id' => '\d+'], methods: ['POST'])]
	public function index(int $user_id, ?UserInterface $user): Response
	{
		if (!$user) {
			return new JsonResponse(status: Response::HTTP_UNAUTHORIZED);
		}

		if ($user->getId() === $user_id) {
			return new JsonResponse(['message' => 'You can\'t follow yourself'], Response::HTTP_BAD_REQUEST);
		}

		$followed = $this->entityManager->getRepository(User::class)->find($user_id);

		if (!$followed) {
			return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
		}

		/** @var FollowRepository $followerRepository */
		$followerRepository = $this->entityManager->getRepository(Follow::class);

		if ($followed->followedBy($user)) {
			return new JsonResponse(['message' => 'You already follow this user'], Response::HTTP_BAD_REQUEST);
		}

		$followerRepository->follow($user, $followed);

		return new JsonResponse(['message' => 'Followed'], Response::HTTP_OK);
	}

	/**
	 * @param int $user_id
	 * @param User|null $user
	 * @return Response
	 */
	#[Route('/user/{user_id}/follow', name: 'unfollow', requirements: ['user_id' => '\d+'], methods: ['DELETE'])]
	public function unfollow(int $user_id, ?UserInterface $user): Response
	{
		if (!$user) {
			return new JsonResponse(status: Response::HTTP_UNAUTHORIZED);
		}

		if ($user->getId() === $user_id) {
			return new JsonResponse(['message' => 'You can\'t unfollow yourself'], Response::HTTP_BAD_REQUEST);
		}

		$followed = $this->entityManager->getRepository(User::class)->find($user_id);

		if (!$followed) {
			return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
		}

		/** @var FollowRepository $followerRepository */
		$followerRepository = $this->entityManager->getRepository(Follow::class);

		if (!$followed->followedBy($user)) {
			return new JsonResponse(['message' => 'You don\'t follow this user'], Response::HTTP_BAD_REQUEST);
		}

		$followerRepository->unfollow($user, $followed);

		return new JsonResponse(['message' => 'Unfollowed'], Response::HTTP_OK);
	}
}
