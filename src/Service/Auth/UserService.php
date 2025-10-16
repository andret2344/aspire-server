<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Auth\ChangePasswordRequest;
use App\Dto\Auth\RegisterUserRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function count;

final readonly class UserService
{
	public function __construct(private EntityManagerInterface      $entityManager,
								private UserPasswordHasherInterface $passwordHasher,
								private ValidatorInterface          $validator) {}

	public function register(RegisterUserRequest $dto): User
	{
		$errors = $this->validator->validate($dto);
		if (count($errors) > 0) {
			throw new InvalidArgumentException((string)$errors);
		}

		$existing = $this->entityManager->getRepository(User::class)
			->findOneBy(['email' => $dto->email]);
		if ($existing) {
			throw new DomainException('Email is already in use.');
		}

		$user = new User($dto->email);
		$user->setPlainPassword($dto->password);

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		return $user;
	}

	public function changePassword(User $user, ChangePasswordRequest $dto): void
	{
		$errors = $this->validator->validate($dto);
		if (count($errors) > 0) {
			throw new InvalidArgumentException((string)$errors);
		}
		if (!$dto->passwordsMatch()) {
			throw new DomainException('Passwords do not match.');
		}

		if (!$this->passwordHasher->isPasswordValid($user, $dto->oldPassword)) {
			throw new DomainException('Incorrect old password!');
		}

		$user->setPlainPassword($dto->password);
		$this->entityManager->flush();
	}
}
