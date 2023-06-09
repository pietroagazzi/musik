<?php

namespace App\Controller\Security;

use App\Entity\EmailVerificationRequest;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\{EmailVerifier, UserAuthenticator};
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\{CurrentUser, IsGranted};
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Controller used to manage registration
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
class RegistrationController extends AbstractController
{
	/**
	 * time to wait before you can request another verification link
	 *
	 * @var int
	 */
	private const VERIFICATION_EMAIL_WAIT_TIME = 60;

	/**
	 * @param EmailVerifier $emailVerifier
	 */
	public function __construct(
		private readonly EmailVerifier $emailVerifier
	)
	{
	}

	/**
	 * create a new user account and send a verification email.
	 *
	 * @param Request $request
	 * @param UserPasswordHasherInterface $userPasswordHasher
	 * @param UserAuthenticatorInterface $userAuthenticator
	 * @param UserAuthenticator $authenticator
	 * @param EntityManagerInterface $entityManager
	 * @return Response
	 * @throws TransportExceptionInterface
	 */
	#[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
	public function register(
		Request                     $request,
		UserPasswordHasherInterface $userPasswordHasher,
		UserAuthenticatorInterface  $userAuthenticator,
		UserAuthenticator           $authenticator,
		EntityManagerInterface      $entityManager
	): Response
	{
		// if the user is already logged in, redirect to home page
		if ($this->getUser()) {
			return $this->redirectToRoute('app_home');
		}

		// create a new user
		$user = new User();

		// create the registration form
		$form = $this->createForm(RegistrationFormType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			// encode the plain password
			$user
				->setPassword(
					$userPasswordHasher->hashPassword(
						$user,
						$form->get('plainPassword')->getData()
					)
				);

			// create a new email verification request
			$emailConfirmationRequired = new EmailVerificationRequest();
			$emailConfirmationRequired->setUser($user);

			// persist the user and the email verification request
			$entityManager->persist($user);
			$entityManager->persist($emailConfirmationRequired);
			$entityManager->flush();

			// generate a signed url and email it to the user
			$this->emailVerifier->sendEmailConfirmation(
				'app_verify_email',
				$user,
				(new TemplatedEmail())
					->from(new Address('noreply@musik.com', 'Musik App'))
					->to($user->getEmail())
					->subject('Please Confirm your Email')
					->htmlTemplate('security/registration/confirmation_email.html.twig')
			);

			// show a message saying that an email has been sent
			$this->addFlash('success', 'Your account has been created.');

			// authenticate the user after registration
			return $userAuthenticator->authenticateUser(
				$user,
				$authenticator,
				$request
			);
		}

		// render the registration form
		return $this->render(
			'security/registration/register.html.twig', [
				'form' => $form,
			]
		);
	}

	/**
	 * confirm the user's email address.
	 *
	 * @param Request $request
	 * @param TranslatorInterface $translator
	 * @return Response
	 */
	#[Route('/verify/email', name: 'app_verify_email', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
	{
		// validate email confirmation link, sets User::isVerified=true and persists
		try {
			// validate email confirmation link, sets User::isVerified=true and persists
			$this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
		} catch (VerifyEmailExceptionInterface $exception) {
			// if the verification link is invalid, show a message and redirect to 'app_register' route
			$this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

			return $this->redirectToRoute('app_register');
		}

		// if the user has already verified their email address, redirect to the home page
		$this->addFlash('success', 'Your email address has been verified.');
		return $this->redirectToRoute('app_home');
	}

	/**
	 * resend verification email.
	 *
	 * @param EntityManagerInterface $entityManager
	 * @param User $user
	 * @return Response
	 * @throws TransportExceptionInterface
	 */
	#[Route('/verify/email/resend', name: 'app_verify_email_resend', methods: ['GET'])]
	public function resendVerificationEmail(
		EntityManagerInterface       $entityManager,
		#[CurrentUser] UserInterface $user
	): Response
	{
		// check if user has already verified their email
		if ($user->isVerified()) {
			$this->addFlash('warning', 'Your email address has already been verified.');
			return $this->redirectToRoute('app_home');
		}

		// get last verification request
		// if user has never requested a verification email, $lastVerificationRequest will be false
		$lastVerificationRequest = $user->getEmailVerificationRequests()->last();


		// if the user has already requested a verification email
		if ($lastVerificationRequest) {
			$lastVerificationSentAt = $lastVerificationRequest->getRequestedAt()->getTimestamp();

			// calculate time elapsed since last verification email sent
			$secondsSinceLastVerification = (new DateTime('now'))->getTimestamp() - $lastVerificationSentAt;

			// check if user has to wait before requesting another verification email
			if ($secondsSinceLastVerification < self::VERIFICATION_EMAIL_WAIT_TIME) {
				// calculate time left before user can request another verification email
				$timeLeft = self::VERIFICATION_EMAIL_WAIT_TIME - $secondsSinceLastVerification;

				$this->addFlash(
					'warning',
					"Wait $timeLeft seconds before resend verification email."
				);

				return $this->redirectToRoute('app_home');
			}
		} else {
			// if the user has never requested a verification email
			$lastVerificationRequest = new EmailVerificationRequest();
			$lastVerificationRequest->setUser($user);
		}

		// update last verification sent at
		$lastVerificationRequest->setRequestedAt(new DateTimeImmutable('now'));

		// persist the last verification request
		$entityManager->persist($lastVerificationRequest);
		$entityManager->flush();

		// generate a signed url and email it to the user
		$this->emailVerifier->sendEmailConfirmation(
			'app_verify_email',
			$user,
			(new TemplatedEmail())
				->from(new Address('noreply@musik.com', 'Musik App'))
				->to($user->getEmail())
				->subject('Please Confirm your Email')
				->htmlTemplate('security/registration/confirmation_email.html.twig')
		);

		// show a message saying that an email has been sent
		$this->addFlash('success', 'Verification email has been sent.');
		return $this->redirectToRoute('app_home');
	}
}
