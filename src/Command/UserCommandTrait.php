<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function sprintf;

trait UserCommandTrait
{
    private readonly ValidatorInterface $validator;
    private readonly UserPasswordHasherInterface $passwordHasher;
    private readonly EntityManagerInterface $entityManager;

    /**
     * @throws Exception
     */
    protected function getUser(mixed $userIdentifier): User|null
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($userIdentifier);

        if (!$user) {
            throw new Exception(sprintf('User "%s" not found', $userIdentifier));
        }

        return $user;
    }

    /**
     * @throws Exception
     */
    protected function validateUser(User $user, string $propriety = null): void
    {
        $errors = $this->validator->validate($user);

        if ($propriety === null and $errors->count() > 0) {
            throw new Exception(sprintf('User "%s" is not valid', $user->getUsername()));
        }

        if ($propriety !== null and $errors->count() > 0) {
            foreach ($errors as $error) {
                if ($error->getPropertyPath() === $propriety) {
                    throw new Exception(sprintf(
                        'User "%s" is not valid: %s',
                        $user->getUsername(),
                        $error->getMessage()
                    ));
                }
            }
        }
    }
}