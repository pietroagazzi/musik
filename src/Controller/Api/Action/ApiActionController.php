<?php

namespace App\Controller\Api\Action;

use App\Entity\User;
use App\Repository\FollowRepository;
use App\Repository\UserRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Throwable;


/**
 * Api action controller.
 *
 * Contains routes for actions used by the front-end.
 * The difference between the action apis and the common apis is that the action apis requires:
 * - a fully authenticated user
 * - a csrf token
 * - an ajax request (XMLHttpRequest)
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
#[Route('/api/action', name: 'api_action_')]
class ApiActionController extends AbstractController
{
	public function __construct(
		private readonly UserRepository   $userRepository,
		private readonly FollowRepository $followRepository,
	)
	{
	}

	/**
	 * follow the given user
	 *
	 * @param int $user_id the id of the user to follow
	 * @param User $user the current user
	 * @return Response
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Throwable
	 */
	#[Route(
		'/user/{user_id}/follow',
		name: 'follow',
		requirements: ['user_id' => '\d+'],
		methods: ['POST'],
		# condition: 'request.isXmlHttpRequest()'
	)]
	public function followAction(
		int                          $user_id,
		#[CurrentUser] UserInterface $user
	): Response
	{
		// generate new csrf token
		$newCsrfToken = $this->generateCsrfToken('api_action');
		$response = new JsonResponse;

		// set new csrf token
		$response
			->headers->set('X-CSRF-Token', $newCsrfToken);

		// get user to unfollow
		if (!$followed = $this->userRepository->find($user_id)) {
			return $response
				->setData(['message' => 'User not found'])
				->setStatusCode(Response::HTTP_NOT_FOUND);
		}

		// check if user is trying to follow himself
		if ($user->getId() === $user_id) {
			return new JsonResponse([
				'csrf_token' => $newCsrfToken,
				'message' => 'You can\'t follow yourself'
			], Response::HTTP_BAD_REQUEST);
		}

		// check if user is already following the user
		if ($followed->followedBy($user)) {
			return $response
				->setData(['message' => 'You already follow this user'])
				->setStatusCode(Response::HTTP_BAD_REQUEST);
		}

		// follow user
		$this->followRepository->follow($user, $followed);

		// return response
		return $response
			->setData(['message' => 'User followed'])
			->setStatusCode(Response::HTTP_OK);
	}

	/**
	 * generate a new csrf token
	 *
	 * @param string $tokenId the id of the token
	 * @return string the new csrf token
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	private function generateCsrfToken(string $tokenId): string
	{
		/** @var CsrfTokenManagerInterface $tokenProvider */
		$tokenProvider = $this->container->get('security.csrf.token_manager');
		return $tokenProvider->getToken($tokenId)->getValue();
	}

	/**
	 * unfollow the given user
	 *
	 * @param int $user_id the id of the user to unfollow
	 * @param User $user the current user
	 * @return JsonResponse
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Throwable
	 */
	#[Route(
		'/user/{user_id}/follow',
		name: 'unfollow',
		requirements: ['user_id' => '\d+'],
		methods: ['DELETE'],
		# condition: 'request.isXmlHttpRequest()'
	)]
	public function unfollowAction(
		int                          $user_id,
		#[CurrentUser] UserInterface $user
	): JsonResponse
	{
		// generate new csrf token
		$newCsrfToken = $this->generateCsrfToken('api_action');
		$response = new JsonResponse;

		// set new csrf token
		$response
			->headers->set('X-CSRF-Token', $newCsrfToken);

		// get user to unfollow
		if (!$followed = $this->userRepository->find($user_id)) {
			return $response
				->setData(['message' => 'User not found'])
				->setStatusCode(Response::HTTP_NOT_FOUND);
		}

		// check if user is trying to unfollow himself
		if (!$followed->followedBy($user)) {
			return $response
				->setData(['message' => 'You don\'t follow this user'])
				->setStatusCode(Response::HTTP_BAD_REQUEST);
		}

		// unfollow user
		$this->followRepository->unfollow($user, $followed);

		return $response
			->setData(['message' => 'User unfollowed'])
			->setStatusCode(Response::HTTP_OK);
	}
}
