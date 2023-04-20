<?php

namespace App\Command;

use App\Entity\ResetPasswordRequest;
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

#[AsCommand(
    name: 'app:user:show',
    description: 'Add a short description for your command',
)]
class UserShowCommand extends Command
{
    use UserCommandTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::REQUIRED, 'The identifier of the user')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all information about the user');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userIdentifier = $input->getArgument('user');
        $all = $input->getOption('all');

        $user = $this->getUser($userIdentifier);

        if (!$user) {
            throw new Exception("User with identifier \"$userIdentifier\" not found");
        }

        $io->table(
            ['Property', 'Value'],
            [
                ['Username', $user->getUsername()],
                ['Email', $user->getEmail()],
                ['Roles', implode(', ', $user->getRoles())],
                ['Verified', $user->isVerified() ? 'Yes' : 'No'],
                ['LastVerifiedAt', $user->getLastVerificationSentAt() ? $user->getLastVerificationSentAt()->format('d-m-Y-H:i:s') : 'Never'],
            ]
        );

        if ($all) {
            // Show pending password reset requests
            $passwordResetRequest = $this->entityManager
                ->getRepository(ResetPasswordRequest::class)
                ->findBy(['user' => $user]);

            $io->text("Password reset requests:");
            $io->table(
                ['Id', 'ExpiresAt', 'Expired', 'HashedToken'],
                array_map(fn(ResetPasswordRequest $request) => [
                    $request->getId(),
                    $request->getExpiresAt()->format('d-m-Y H:i:s'),
                    $request->isExpired() ? 'Yes' : 'No',
                    $request->getHashedToken(),
                ], $passwordResetRequest)
            );
        }

        return Command::SUCCESS;
    }
}
