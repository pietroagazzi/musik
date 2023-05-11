<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * controller for security-related actions
 */
class SecurityController extends AbstractController
{
	/**
	 * displays the login form
	 *
	 * @param AuthenticationUtils $authenticationUtils
	 * @param UserInterface|null $user
	 * @return Response
	 */
	#[Route(path: '/login', name: 'app_login')]
	public function login(AuthenticationUtils $authenticationUtils, ?UserInterface $user): Response
	{
		// if the user is already logged in, redirect to the homepage
		if ($user) {
			return $this->redirectToRoute('app_home');
		}

		// get the login error if there is one
		$error = $authenticationUtils->getLastAuthenticationError();

		// last username entered by the user
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render(
			'security/login.html.twig',
			[
				'last_username' => $lastUsername,
				'error' => $error]
		);
	}

	/**
	 * the route for logging out
	 *
	 * @return void
	 */
	#[Route(path: '/logout', name: 'app_logout')]
	public function logout(): void
	{
	}
}
