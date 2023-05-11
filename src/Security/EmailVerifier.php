<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * send and handle email verification
 */
readonly class EmailVerifier
{
	/**
	 * @param VerifyEmailHelperInterface $verifyEmailHelper
	 * @param MailerInterface $mailer
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(
		private VerifyEmailHelperInterface $verifyEmailHelper,
		private MailerInterface            $mailer,
		private EntityManagerInterface     $entityManager
	)
	{
	}

	/**
	 * @param string $verifyEmailRouteName
	 * @param User $user
	 * @param TemplatedEmail $email
	 * @return void
	 * @throws TransportExceptionInterface
	 */
	public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void
	{
		// generate a signed url and email it to the user
		$signatureComponents = $this->verifyEmailHelper->generateSignature(
			$verifyEmailRouteName,
			$user->getId(),
			$user->getEmail()
		);

		$context = $email->getContext();
		$context['signedUrl'] = $signatureComponents->getSignedUrl();
		$context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
		$context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

		$email->context($context);

		// send email
		$this->mailer->send($email);
	}

	/**
	 * @param Request $request
	 * @param User $user
	 * @return void
	 * @throws VerifyEmailExceptionInterface
	 */
	public function handleEmailConfirmation(Request $request, UserInterface $user): void
	{
		$this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());

		$user->setIsVerified(true);

		$this->entityManager->persist($user);
		$this->entityManager->flush();
	}
}
