<?php

namespace App\EventSubscriber\Api;

use App\Controller\Api\Action\ApiActionController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use function is_array;

final readonly class ActionRequestSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private CsrfTokenManagerInterface $csrfTokenManager,
		private Security                  $security
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::CONTROLLER => 'onActionRequest',
		];
	}

	/**
	 * Validate the request for an action api.
	 *
	 * If the request is not valid, the controller is replaced with a JsonResponse.
	 * Are considered valid requests:
	 * - ajax requests
	 * - requests with a valid csrf token
	 * - fully authenticated users
	 *
	 * @param ControllerEvent $event
	 * @return void
	 */
	public function onActionRequest(ControllerEvent $event): void
	{
		$controller = $event->getController();

		if (!is_array($controller)) {
			return;
		}

		// get controller object
		[$controllerObject,] = $controller;

		// check if controller is an instance of ApiActionController
		if (!$controllerObject instanceof ApiActionController) {
			return;
		}

		// check if user is authenticated
		if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
			$event->setController(fn() => new JsonResponse([
				'message' => 'Full authentication is required to access this resource',
			], Response::HTTP_UNAUTHORIZED));

			return;
		}

		// get request
		$request = $event->getRequest();

		// check if request is ajax
		if (!$request->isXmlHttpRequest()) {
			$event->setController(fn() => new JsonResponse([
				'message' => 'This action requires an ajax request',
			], Response::HTTP_BAD_REQUEST));

			return;
		}

		// check csrf token
		$token = $request->headers->get('X-CSRF-Token');

		// validate csrf token
		if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('api_action', $token))) {
			$event->setController(fn() => new JsonResponse([
				'message' => 'Invalid CSRF token',
			], Response::HTTP_FORBIDDEN));
		}
	}
}