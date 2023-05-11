<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function sprintf;

#[AsCommand(
	name: 'app:user:edit',
	description: 'Edits a user'
)]
final class UserEditCommand extends Command
{
	use UserCommandTrait;

	/**
	 * @param ValidatorInterface $validator
	 * @param UserPasswordHasherInterface $passwordHasher
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(
		private readonly ValidatorInterface          $validator,
		private readonly UserPasswordHasherInterface $passwordHasher,
		private readonly EntityManagerInterface      $entityManager
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
			->addArgument('user', InputArgument::OPTIONAL, 'User identifier')
			->addOption('delete', 'D', InputOption::VALUE_NONE, 'Delete the user')
			->addOption('verified', null, InputOption::VALUE_NONE, 'Set the user as verified')
			->addOption('unverified', null, InputOption::VALUE_NONE, 'Set the user as unverified');
	}

	/**
	 * @inheritDoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$userIdentifier = $input->getArgument('user');

		// get the user
		$user = $this->getUser($userIdentifier) ?? throw new InvalidArgumentException(
			sprintf('User "%s" does not exist', $userIdentifier)
		);

		// if the option 'delete' is set, delete the user
		if ($input->getOption('delete')) {
			$this->entityManager->remove($user);
			$this->entityManager->flush();

			// display a success message
			$io->success(sprintf('User "%s" deleted', $user->getUsername()));
			return Command::SUCCESS;
		}

		// if the option 'verified' is set, set the user as verified
		if ($input->getOption('verified')) {
			$user->setIsVerified(true);
		}

		// if the option 'unverified' is set, set the user as unverified
		if ($input->getOption('unverified')) {
			$user->setIsVerified(false);
		}

		// ask the username
		$io->ask(
			'What is the username of the user ? ', $user->getUsername(), function ($username) use ($user) {
			$user->setUsername($username);
			$this->validateUser($user, 'username');
		}
		);

		// ask the email
		$io->ask(
			'What is the email of the user ? ', $user->getEmail(), function ($email) use ($user) {
			$user->setEmail($email);
			$this->validateUser($user, 'email');
		}
		);

		// ask the password
		$io->ask(
			'What is the password of the user ? ', null, function ($password) use ($user) {
			if ($password) {
				$user->setPassword($this->passwordHasher->hashPassword($user, $password));
			}
		}
		);

		// update the user in the database
		$this->entityManager->persist($user);
		$this->entityManager->flush();

		// display a success message
		$io->success(sprintf('User "%s" edited', $user->getUsername()));
		return Command::SUCCESS;
	}
}
