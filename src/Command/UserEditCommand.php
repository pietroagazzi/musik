<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:user:edit',
    description: 'Edits a user'
)]
class UserEditCommand extends Command
{
    use UserCommandTrait;

    public function __construct(
        private readonly ValidatorInterface          $validator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface      $entityManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::OPTIONAL, 'User identifier')
            ->addOption('delete', 'D', InputOption::VALUE_NONE, 'Delete the user')
            ->addOption('verified', null, InputOption::VALUE_NONE, 'Set the user as verified')
            ->addOption('unverified', null, InputOption::VALUE_NONE, 'Set the user as unverified');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userIdentifier = $input->getArgument('user');

        $user = $this->getUser($userIdentifier);

        if ($input->getOption('delete')) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $io->success(sprintf('User "%s" deleted', $user->getUsername()));

            return Command::SUCCESS;
        }

        if ($input->getOption('verified')) {
            $user->setIsVerified(true);
        }

        if ($input->getOption('unverified')) {
            $user->setIsVerified(false);
        }

        $io->ask('What is the username of the user?', $user->getUsername(), function ($username) use ($user) {
            $user->setUsername($username);
            $this->validateUser($user, 'username');
        });

        $io->ask('What is the email of the user?', $user->getEmail(), function ($email) use ($user) {
            $user->setEmail($email);
            $this->validateUser($user, 'email');
        });

        $io->ask('What is the password of the user?', null, function ($password) use ($user) {
            if ($password)
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        });

        $this->entityManager->flush();

        $io->success(sprintf('User "%s" edited', $user->getUsername()));

        return Command::SUCCESS;
    }
}
