<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\UserValidator;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Stopwatch\Stopwatch;
use function count;
use function sprintf;

/**
 * Used to list all users in the database or a specific one
 * @see https://symfony.com/doc/current/console.html
 *
 * Examples:
 *  - bin/console app:users --limit 20 --offset 10
 *  - bin/console app:users --limit 15 "user.getFollowers().count() > 100"
 *  - bin/console app:users "not (user.getEmail() matches '/.\@email.com/i')" -v
 *  - bin/console app:users --id 5
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
#[AsCommand(
	name: 'app:user:list',
	description: 'Lists all users in the database',
	aliases: ['app:users:list', 'app:users']
)]
final class ListUsersCommand extends Command
{
	private SymfonyStyle $io;

	private ExpressionLanguage $expressionLanguage;

	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly UserValidator  $validator,
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

		# TODO: Add cache adapter to the expression language
		$this->expressionLanguage = new ExpressionLanguage;
	}

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this
			/* @see https://symfony.com/doc/current/components/expression_language.html */
			->addArgument('filter', InputArgument::OPTIONAL, 'Filter by expression')
			->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'The maximum number of users to display', 10)
			->addOption('offset', 'o', InputOption::VALUE_REQUIRED, 'The number of users to skip', 0)
			->addOption('username', null, InputOption::VALUE_REQUIRED, 'The username of the user to search for')
			->addOption('email', null, InputOption::VALUE_REQUIRED, 'The email of the user to search for')
			->addOption('id', null, InputOption::VALUE_REQUIRED, 'The id of the user to search for');
	}

	/**
	 * @inheritDoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output): void
	{
		if ($username = $input->getOption('username')) {
			$this->validator->validateUsername($username);
		}

		if ($email = $input->getOption('email')) {
			$this->validator->validateEmail($email);
		}

		if (($id = $input->getOption('id')) && !is_numeric($id)) {
			throw new InvalidArgumentException('The id must be a number');
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		/* @see https://symfony.com/doc/3.4/components/stopwatch.html */
		$stopwatch = new Stopwatch;
		$stopwatch->start('list-users-command');

		// get options
		$limit = $input->getOption('limit');
		$offset = $input->getOption('offset');
		$username = $input->getOption('username');
		$email = $input->getOption('email');
		$id = $input->getOption('id');

		$users = $this->findUsers($limit, $offset, $email, $username, $id);

		// filter users by expression
		if ($filter = $input->getArgument('filter')) {
			$users = $this->filterUsers($users, $filter);
		}

		$event = $stopwatch->stop('list-users-command');

		// show table of users
		$this->io->table(
			['Id', 'Email', 'Username', 'Created At', 'Updated At'],
			array_map(
				static fn(User $user) => [
					$user->getId(),
					$user->getEmail(),
					$user->getUsername(),
					$user->getCreatedAt()?->format('Y-m-d H:i:s'),
					$user->getUpdatedAt()?->format('Y-m-d H:i:s'),
				],
				$users
			)
		);

		if ($output->isVerbose()) {
			$this->io->text(sprintf('Found %d users', count($users)));
			$this->io->comment(sprintf(
				'Elapsed time: %.2f ms / Consumed memory: %.2f MB',
				$event->getDuration(), $event->getMemory() / (1024 ** 2)
			));
		}

		return Command::SUCCESS;
	}

	private function findUsers(int $limit, int $offset, ?string $email, ?string $username, ?string $id): array
	{
		$queryBuilder = $this->userRepository
			->createQueryBuilder('u')
			->setMaxResults($limit)
			->setFirstResult($offset);

		if ($email) {
			$queryBuilder->andWhere('u.email = :email')->setParameter('email', $email);
		}

		if ($username) {
			$queryBuilder->andWhere('u.username = :username')->setParameter('username', $username);
		}

		if ($id) {
			$queryBuilder->andWhere('u.id = :id')->setParameter('id', $id);
		}

		return $queryBuilder->getQuery()->getResult();
	}

	private function filterUsers(array $users, string $filter): array
	{
		return array_filter(
			$users,
			fn(User $user) => $this->expressionLanguage->evaluate($filter, ['user' => $user])
		);
	}
}
