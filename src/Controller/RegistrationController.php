<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\UserAuthenticator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    /**
     * time to wait before you can request another verification link
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
     * Create a new user account and send a verification email.
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
        // if user is already logged in, redirect to home page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
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
                )
                ->setLastVerificationSentAt(new DateTime('now'));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@musik.com', 'Musik App'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('security/registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Your account has been created.');

            // authenticate the user after registration
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('security/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Confirm the user's email address.
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
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_home');
    }

    /**
     * Resend verification email.
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

        $lastVerificationSentAt = $user->getLastVerificationSentAt()->getTimestamp();
        // calculate time elapsed since last verification email sent
        $secondsSinceLastVerification = (new DateTime('now'))->getTimestamp() - $lastVerificationSentAt;

        // check if user has to wait before requesting another verification email
        if ($secondsSinceLastVerification < self::VERIFICATION_EMAIL_WAIT_TIME) {
            $timeLeft = self::VERIFICATION_EMAIL_WAIT_TIME - $secondsSinceLastVerification;

            $this->addFlash(
                'warning',
                "Wait $timeLeft seconds before resend verification email."
            );

            return $this->redirectToRoute('app_home');
        }

        // update last verification sent at
        $user->setLastVerificationSentAt(new DateTime('now'));
        $entityManager->persist($user);
        $entityManager->flush();

        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@musik.com', 'Musik App'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('security/registration/confirmation_email.html.twig')
        );

        $this->addFlash('success', 'Verification email has been sent.');
        return $this->redirectToRoute('app_home');
    }
}
