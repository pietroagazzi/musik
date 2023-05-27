<?php

namespace App\Utils;

use App\Entity\User;
use HumbugBox380\Composer\Semver\Constraint\ConstraintInterface;
use InvalidArgumentException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function Symfony\Component\String\u;

final readonly class UserValidator
{

	public function __construct(
		private ValidatorInterface $validator
	)
	{
	}

	/**
	 * @throws InvalidArgumentException if the value is invalid
	 */
	public function validateUsername(?string $username): ?string
	{
		$this->validate($username, 'username');

		return $username;
	}

	/**
	 * @throws InvalidArgumentException if the value is invalid
	 */
	public function validate(mixed $value, string $propriety): void
	{
		$constraints = $this->getConstraints($propriety);

		$violations = $this->validator->validate($value, $constraints);

		if ($violations->count() > 0) {
			# get the first violation
			$violation = $violations->get(0);

			throw new InvalidArgumentException($violation->getMessage());
		}
	}

	/**
	 * @param string $propriety the propriety to get the constraints for
	 * @return ConstraintInterface[] the constraints for the propriety
	 */
	private function getConstraints(string $propriety): array
	{
		# get the constraints for the propriety
		/** @var ClassMetadata $classMetadata */
		$classMetadata = $this->validator->getMetadataFor(User::class);

		return $classMetadata
			->getPropertyMetadata($propriety)[0]
			->getConstraints();
	}

	/**
	 * @throws InvalidArgumentException if the value is invalid
	 */
	public function validateEmail(?string $email): ?string
	{
		$this->validate($email, 'email');

		return $email;
	}

	/**
	 * @throws InvalidArgumentException if the value is invalid
	 */
	public function validatePassword(?string $password): string
	{
		if (empty($password)) {
			throw new InvalidArgumentException('The password cannot be empty');
		}

		if (u($password)->trim()->length() < 8) {
			throw new InvalidArgumentException('The password must be at least 8 characters long');
		}

		return $password;
	}
}