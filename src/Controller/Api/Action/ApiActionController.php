<?php

namespace App\Controller\Api\Action;

use App\Entity\Follow;
use App\Entity\User;
use App\Repository\FollowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
	 * @param Request $request
	 * @param int $user_id
	 * @param User|null $user
	 * @return Response
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	#[Route('/user/{user_id}/follow', name: 'follow', requirements: ['user_id' => '\d+'], methods: ['POST', 'DELETE'])]
	public function index(Request $request, int $user_id, ?UserInterface $user): Response
	{
		if (!$user) {
			return new JsonResponse(status: Response::HTTP_UNAUTHORIZED);
		}

		// check csrf token
		$csrfToken = $request->headers->get('X-CSRF-Token');

		if (!$this->isCsrfTokenValid('api_action', $csrfToken)) {
			return new JsonResponse(['message' => 'Invalid CSRF token'], 406);
		}

		// get user to follow
		$followed = $this->entityManager->getRepository(User::class)->find($user_id);

		// check if user exists
		if (!$followed) {
			return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
		}

		// check if user is not trying to follow himself
		if ($user->getId() === $user_id) {
			return new JsonResponse(['message' => 'You can\'t follow yourself'], Response::HTTP_BAD_REQUEST);
		}

		// get follow repository
		/** @var FollowRepository $followerRepository */
		$followerRepository = $this->entityManager->getRepository(Follow::class);

		// check if user already follow this user on POST request
		if ($followed->followedBy($user) and $request->isMethod('POST')) {
			return new JsonResponse(['message' => 'You already follow this user'], Response::HTTP_BAD_REQUEST);
		}

		// check if user don't follow this user on DELETE request
		if (!$followed->followedBy($user) and $request->isMethod('DELETE')) {
			return new JsonResponse(['message' => 'You don\'t follow this user'], Response::HTTP_BAD_REQUEST);
		}

		// follow or unfollow user
		if ($request->isMethod('DELETE')) {
			$followerRepository->unfollow($user, $followed);
		} elseif ($request->isMethod('POST')) {
			$followerRepository->follow($user, $followed);
		}

		// generate new csrf token
		$tokenProvider = $this->container->get('security.csrf.token_manager');
		$token = $tokenProvider->getToken('api_action')->getValue();

		return new JsonResponse(['csrf_token' => $token], Response::HTTP_OK);
	}
}
