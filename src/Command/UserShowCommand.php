<?php

namespace App\Command;

use App\Entity\ResetPasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:user:show',
	description: 'Add a short description for your command',
)]
class UserShowCommand extends Command
{
	use UserCommandTrait;

	/**
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(
		private readonly EntityManagerInterface $entityManager
	)
	{
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this
			->addArgument('user', InputArgument::REQUIRED, 'The identifier of the user')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all information about the user');
	}

	/**
	 * @inheritDoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		// retrieve the arguments
		$userIdentifier = $input->getArgument('user');
		$all = $input->getOption('all');

		// get the user
		$user = $this->getUser($userIdentifier) ?? throw new InvalidArgumentException(
			sprintf('User "%s" does not exist', $userIdentifier)
		);

		// get the last verification request time
		$lastVerification = $user->getEmailVerificationRequests()->last();

		// show the user information
		$io->table(
			['Property', 'Value'],
			[
				['username', $user->getUsername()],
				['email', $user->getEmail()],
				['roles', implode(', ', $user->getRoles())],
				['is verified', $user->isVerified() ? 'Yes' : 'No'],
				['last email verification', $lastVerification ? $lastVerification
					->getRequestedAt()
					->format('d-m-Y H:i:s') : 'Never'
				],
			]
		);

		// if the 'all' option is set, show more information
		if ($all) {
			// show pending password reset requests
			$passwordResetRequest = $this->entityManager
				->getRepository(ResetPasswordRequest::class)
				->findBy(['user' => $user]);

			// show the password reset requests
			$io->text("Password reset requests:");

			if (empty($passwordResetRequest)) {
				// if there are no password reset requests, show a message
				$io->text("No password reset requests found.");

				return Command::SUCCESS;
			}

			$io->table(
				['Id', 'ExpiresAt', 'Expired', 'HashedToken'],
				array_map(
					fn(ResetPasswordRequest $request) => [
						$request->getId(),
						$request->getExpiresAt()->format('d-m-Y H:i:s'),
						$request->isExpired() ? 'Yes' : 'No',
						$request->getHashedToken(),
					], $passwordResetRequest
				)
			);
		}

		return Command::SUCCESS;
	}
}
