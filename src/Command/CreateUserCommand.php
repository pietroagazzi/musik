<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
	name: 'app:user:create',
	description: 'Creates a new user',
)]
final class CreateUserCommand extends Command
{
	use UserCommandTrait;

	/**
	 * @var array|string[] $exampleUsernames An array of example usernames
	 */
	protected static array $exampleUsernames = [
		'wouter',
		'jordi',
		'ryan',
		'fabien',
	];

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
			->addOption('verified', null, InputOption::VALUE_NONE, 'Set the user as verified');
	}

	/**
	 * @inheritDoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		// create a new user
		$user = new User();

		// choose a random username
		$randomUsername = self::$exampleUsernames[array_rand(self::$exampleUsernames)];

		// ask for the username
		$username = $io->ask(
			"What is the username of the user?", $randomUsername, function ($username) use ($user) {
			$user->setUsername($username);

			// validate the username
			$this->validateUser($user, 'username');

			return $username;
		}
		);

		// ask for the email
		$io->ask(
			'What is the email of the user?', "$username@example.com", function ($email) use ($user) {
			$user->setEmail($email);

			// validate the email
			$this->validateUser($user, 'email');
		}
		);

		// ask for the password
		$io->askHidden(
			'What is the password of the user?', function ($plainPassword) use ($user) {
			// hash the password
			$hash = $this->passwordHasher->hashPassword($user, $plainPassword);
			$user->setPassword($hash);
		}
		);

		// ask for the roles
		$io->ask(
			'What are the roles of the user? (separate with a empty space)', null, function ($roles) use ($user) {
			if (!empty($roles)) {
				$user->setRoles(explode(' ', $roles));
			}
		}
		);

		// set the user as verified if the option is set
		if ($input->getOption('verified')) {
			$user->setIsVerified(true);
		}

		// save the user to the database
		$this->entityManager->persist($user);
		$this->entityManager->flush();

		// show a success message
		$io->success("User $username (ID: {$user->getId()}) was created successfully!");

		// show the next steps
		if (!$user->isVerified()) {
			$io->text("Now: Verify the user's email");
		}

		$io->text("Next: Review the user's profile by running: \"php bin/console app:user:show {$user->getId()}\"");
		$io->text("Than: Update the user's profile by running: \"php bin/console app:user:update {$user->getId()}\"");

		return Command::SUCCESS;
	}
}
