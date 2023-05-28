<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use function sprintf;

/**
 * Used to delete a user from the database
 * @see https://symfony.com/doc/current/console.html
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
#[AsCommand(
	name: 'app:user:delete',
	description: 'Deletes a user from the database',
	aliases: ['app:user:remove']
)]
final class DeleteUserCommand extends Command
{
	private SymfonyStyle $io;

	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly UserRepository         $userRepository,
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
			->addOption('email', null, InputOption::VALUE_REQUIRED, 'The email of the user')
			->addOption('username', null, InputOption::VALUE_REQUIRED, 'The username of the user')
			->addOption('id', null, InputOption::VALUE_REQUIRED, 'The id of the user');
	}

	/**
	 * @inheritDoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output): void
	{
		$email = $input->getOption('email');
		$username = $input->getOption('username');
		$id = $input->getOption('id');

		if (!$email && !$username && !$id) {
			throw new InvalidArgumentException('You must provide at least one of the following options: email, username, id');
		}
	}

	/**
	 * @inheritDoc
	 * @throws NonUniqueResultException If more than one user is found
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		/* @see https://symfony.com/doc/3.4/components/stopwatch.html */
		$stopwatch = new Stopwatch();
		$stopwatch->start('delete-user-command');

		$email = $input->getOption('email');
		$username = $input->getOption('username');
		$id = $input->getOption('id');

		$user = $this->findUser($email, $username, $id);

		// if the user does not exist, throw an exception
		if (!$user) {
			$this->io->error(sprintf('User "%s" not found', $email ?? $username ?? $id));

			return Command::FAILURE;
		}

		$this->entityManager->remove($user);
		$this->entityManager->flush();

		$this->io->success(sprintf('User "%s" successful deleted', $user->getUsername()));

		$event = $stopwatch->stop('delete-user-command');

		// if verbose mode is enabled (--verbose), display some extra information about the command execution
		if ($output->isVerbose()) {
			$this->io->comment(sprintf(
				'Elapsed time: %.2f ms / Consumed memory: %.2f MB',
				$event->getDuration(), $event->getMemory() / (1024 ** 2)
			));
		}

		return Command::SUCCESS;
	}

	/**
	 * @throws NonUniqueResultException if more than one user is found
	 */
	private function findUser(?string $email, ?string $username, ?string $id): User|null
	{
		$queryBuilder = $this->userRepository
			->createQueryBuilder('u');

		if ($email) {
			$queryBuilder->andWhere('u.email = :email')->setParameter('email', $email);
		}

		if ($username) {
			$queryBuilder->andWhere('u.username = :username')->setParameter('username', $username);
		}

		if ($id) {
			$queryBuilder->andWhere('u.id = :id')->setParameter('id', $id);
		}

		return $queryBuilder->getQuery()->getOneOrNullResult();
	}
}
