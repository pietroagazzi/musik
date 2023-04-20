<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
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
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a new user',
)]
class CreateUserCommand extends Command
{
    use UserCommandTrait;

    protected static array $exampleUsernames = [
        'wouter',
        'jordi',
        'ryan',
        'fabien',
    ];

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
            ->addOption('verified', null, InputOption::VALUE_NONE, 'Set the user as verified');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User();

        $randomUsername = self::$exampleUsernames[array_rand(self::$exampleUsernames)];

        $username = $io->ask("What is the username of the user?", $randomUsername, function ($username) use ($user) {
            $user->setUsername($username);
            $this->validateUser($user, 'username');
        });

        $io->ask('What is the email of the user?', "$username@example.com", function ($email) use ($user) {
            $user->setEmail($email);
            $this->validateUser($user, 'email');
        });

        $io->askHidden('What is the password of the user?', function ($plainPassword) use ($user) {
            $hash = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hash);
        });

        $io->ask('What are the roles of the user? (separate with a empty space)', null, function ($roles) use ($user) {
            if (!empty($roles))
                $user->setRoles(explode(' ', $roles));
        });

        if ($input->getOption('verified')) {
            $user->setIsVerified(true);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("User $username (ID: {$user->getId()}) was created successfully!");

        if (!$user->isVerified()) {
            $io->text("Now: Verify the user's email");
        }

        $io->text("Next: Review the user's profile by running: \"php bin/console app:user:show {$user->getId()}\"");
        $io->text("Than: Update the user's profile by running: \"php bin/console app:user:update {$user->getId()}\"");

        return Command::SUCCESS;
    }
}
