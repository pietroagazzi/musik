<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\UserValidator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function sprintf;

/**
 * Used to create a new user
 */
#[AsCommand(
	name: 'app:user:add',
	description: 'Creates a new user and saves it to the database',
	aliases: ['app:user:create', 'app:user:new']
)]
final class CreateUserCommand extends Command
{
	private SymfonyStyle $io;

	public function __construct(
		private readonly UserValidator               $validator,
		private readonly UserPasswordHasherInterface $passwordHasher,
		private readonly EntityManagerInterface      $entityManager,
		private readonly UserRepository              $userRepository,
	)
	{
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function initialize(InputInterface $input, OutputInterface $output): void
	{
		$this->io = new SymfonyStyle($input, $output);
	}

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this
			->addArgument('email', null, 'The email of the user')
			->addArgument('username', null, 'The username of the user')
			->addArgument('password', null, 'The password of the user')
			->addOption('verified', null, InputOption::VALUE_NONE, 'Set the user as verified');
	}

	/**
	 * @inheritDoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output): void
	{
		// ask for email if it's not defined
		$email = $input->getArgument('email');

		if ($email) {
			$this->io->text('* <info>Email</info>: ' . $email);
		} else {
			$email = $this->io->ask('Email', null, $this->validator->validateEmail(...));
			$input->setArgument('email', $email);
		}

		// ask for username if it's not defined
		$username = $input->getArgument('username');

		if ($username) {
			$this->io->text('* <info>Username</info>: ' . $username);
		} else {
			$username = $this->io->ask('Username', null, $this->validator->validateUsername(...));
			$input->setArgument('username', $username);
		}

		// ask for password if it's not defined
		$password = $input->getArgument('password');

		if ($password) {
			$this->io->text('* <info>Password</info>: ' . $password);
		} else {
			$password = $this->io->askHidden('Password', $this->validator->validatePassword(...));
			$input->setArgument('password', $password);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		/* @see https://symfony.com/doc/3.4/components/stopwatch.html */
		$stopwatch = new Stopwatch();
		$stopwatch->start('create-user-command');

		$email = $input->getArgument('email');
		$username = $input->getArgument('username');
		$plainPassword = $input->getArgument('password');

		$this->validateUserData($email, $username, $plainPassword);

		$user = new User;

		/* @see https://symfony.com/doc/5.4/security.html#registering-the-user-hashing-passwords */
		$password = $this->passwordHasher->hashPassword($user, $plainPassword);

		$user
			->setUsername($username)
			->setEmail($email)
			->setPassword($password);

		if ($input->getOption('verified')) {
			$user->setIsVerified(true);
		}

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		$event = $stopwatch->stop('create-user-command');

		// if verbose mode is enabled (--verbose), display some extra information about the command execution
		if ($output->isVerbose()) {
			$this->io->comment(sprintf(
				'New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB',
				$user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)
			));
		}

		$this->io->success(sprintf(
			'Successfully created user: %s %s',
			$user->getUsername(),
			$user->isVerified() ? '(verified)' : ''
		));

		return Command::SUCCESS;
	}

	protected function validateUserData(string $email, string $username, string $plainPassword): void
	{
		$this->validator->validateEmail($email);
		$this->validator->validateUsername($username);
		$this->validator->validatePassword($plainPassword);

		if ($this->userRepository->findOneBy(['email' => $email])) {
			throw new RuntimeException(sprintf('The email "%s" is already used', $email));
		}

		if ($this->userRepository->findOneBy(['username' => $username])) {
			throw new RuntimeException(sprintf('The username "%s" is already used', $username));
		}
	}
}
