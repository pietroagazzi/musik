<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\{ChangePasswordFormType, ResetPasswordRequestFormType};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * controller used to manage the resetting of the password.
 */
#[Route('/reset')]
class ResetPasswordController extends AbstractController
{
	use ResetPasswordControllerTrait;

	/**
	 * @param ResetPasswordHelperInterface $resetPasswordHelper
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(
		private readonly ResetPasswordHelperInterface $resetPasswordHelper,
		private readonly EntityManagerInterface       $entityManager
	)
	{
	}

	/**
	 * display & process form to request a password reset.
	 *
	 * @throws TransportExceptionInterface
	 */
	#[Route('', name: 'app_reset_request', methods: ['GET', 'POST'])]
	public function request(Request $request, MailerInterface $mailer): Response
	{
		// create the reset password form
		$form = $this->createForm(ResetPasswordRequestFormType::class);
		$form->handleRequest($request);

		// process the form
		if ($form->isSubmitted() && $form->isValid()) {
			return $this->processSendingPasswordResetEmail(
				$form->get('email')->getData(),
				$mailer
			);
		}

		return $this->render(
			'security/reset_password/request.html.twig', [
				'requestForm' => $form->createView(),
			]
		);
	}

	/**
	 * email the user with a link to reset the password.
	 *
	 * @throws TransportExceptionInterface
	 */
	private function processSendingPasswordResetEmail(
		string          $emailFormData,
		MailerInterface $mailer
	): RedirectResponse
	{
		// get the user associated with the email
		$user = $this->entityManager->getRepository(User::class)->findOneBy(
			[
				'email' => $emailFormData,
			]
		);

		// do not reveal whether a user account was found or not.
		if (!$user) {
			return $this->redirectToRoute('app_check_email');
		}

		try {
			// generate a reset token
			$resetToken = $this->resetPasswordHelper->generateResetToken($user);
		} catch (ResetPasswordExceptionInterface) {
			return $this->redirectToRoute('app_check_email');
		}

		// create the email and send it
		$email = (new TemplatedEmail())
			->from(new Address('noreply@misik.com', 'Musik App'))
			->to($user->getEmail())
			->subject('Your password reset request')
			->htmlTemplate('security/reset_password/email.html.twig')
			->context(
				[
					'resetToken' => $resetToken,
					'signedUrl' => $this->generateUrl(
						'app_reset_password', [
						'token' => $resetToken->getToken(),
					], UrlGeneratorInterface::ABSOLUTE_URL
					),
				]
			);

		$mailer->send($email);

		// store the token object in session for retrieval in check-email route.
		$this->setTokenObjectInSession($resetToken);

		return $this->redirectToRoute('app_check_email');
	}

	/**
	 * confirmation page after a user has requested a password reset.
	 */
	#[Route('/check-email', name: 'app_check_email')]
	public function checkEmail(): Response
	{
		// generate a fake token if the user does not exist or someone hit this page directly.
		// this prevents exposing whether a user was found with the given email address or not
		if (null === ($resetToken = $this->getTokenObjectFromSession())) {
			$resetToken = $this->resetPasswordHelper->generateFakeResetToken();
		}

		// render the check email page
		return $this->render(
			'security/reset_password/check_email.html.twig', [
				'resetToken' => $resetToken,
			]
		);
	}

	/**
	 * validates and process the reset URL that the user clicked in their email.
	 */
	#[Route('/reset/{token}', name: 'app_reset_password')]
	public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null): Response
	{
		if ($token) {
			// we store the token in session and remove it from the URL, to avoid the URL being
			// loaded in a browser and potentially leaking the token to 3rd party JavaScript.
			$this->storeTokenInSession($token);

			return $this->redirectToRoute('app_reset_password');
		}

		// get the token from the session and validate it
		$token = $this->getTokenFromSession();

		if (null === $token) {
			throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
		}

		try {
			$user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
		} catch (ResetPasswordExceptionInterface $e) {
			// if the token is invalid, get the reason from the exception and set it as a flash message
			$this->addFlash(
				'reset_password_error', sprintf(
					'%s - %s',
					$translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
					$translator->trans($e->getReason(), [], 'ResetPasswordBundle')
				)
			);

			return $this->redirectToRoute('app_reset_request');
		}

		// the token is valid; allow the user to change their password.
		$form = $this->createForm(ChangePasswordFormType::class);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			// a password reset token should be used only once, remove it.
			$this->resetPasswordHelper->removeResetRequest($token);

			// encode(hash) the plain password, and set it.
			$encodedPassword = $passwordHasher->hashPassword(
				$user,
				$form->get('plainPassword')->getData()
			);

			// update the user's password
			$user->setPassword($encodedPassword);
			$this->entityManager->flush();

			// the session is cleaned up after the password has been changed.
			$this->cleanSessionAfterReset();

			return $this->redirectToRoute('app_home');
		}

		return $this->render(
			'security/reset_password/reset.html.twig', [
				'resetForm' => $form->createView(),
			]
		);
	}
}
