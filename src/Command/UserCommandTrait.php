<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function sprintf;

/**
 * provides common methods for user commands
 */
trait UserCommandTrait
{
	private readonly ValidatorInterface $validator;
	private readonly UserPasswordHasherInterface $passwordHasher;
	private readonly EntityManagerInterface $entityManager;

	/**
	 * retrieves a user from the database by its identifier
	 *
	 * @param mixed $userIdentifier the user identifier
	 * @return User|null the user or null if not found
	 */
	protected function getUser(mixed $userIdentifier): User|null
	{
		return $this->entityManager
			->getRepository(User::class)
			->find($userIdentifier);
	}

	/**
	 * validates a user using the validator constraints defined in the entity
	 *
	 * @param User $user the user to validate
	 * @param string|null $propriety if provided, only the propriety will be validated
	 * @throws InvalidArgumentException if the user is not valid
	 * @see    https://symfony.com/doc/current/reference/constraints.html
	 */
	protected function validateUser(User $user, string $propriety = null): void
	{
		// validate the user
		$errors = $this->validator->validate($user);

		// if the propriety is not defined, throw an exception if the user is not valid
		if ($propriety === null and $errors->count() > 0) {
			throw new InvalidArgumentException(sprintf('User "%s" is not valid', $user->getUsername()));
		} elseif ($propriety !== null and $errors->count() > 0) {
			// if the propriety is defined, throw an exception if the user is not valid
			foreach ($errors as $error) {
				// if the error is related to the propriety, throw an exception
				if ($error->getPropertyPath() === $propriety) {
					throw new InvalidArgumentException(
						sprintf(
							'User "%s" is not valid: %s',
							$user->getUsername(),
							$error->getMessage()
						)
					);
				}
			}
		}
	}
}
