<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Auth\ChangePasswordRequest;
use App\Dto\Auth\RegisterUserRequest;
use App\Entity\User;
use App\Entity\VerificationToken;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function count;

final readonly class UserService
{
	public function __construct(
		private EntityManagerInterface      $entityManager,
		private UserPasswordHasherInterface $passwordHasher,
		private ValidatorInterface          $validator,
	) {}

	public function register(RegisterUserRequest $dto): User
	{
		$errors = $this->validator->validate($dto);
		if (count($errors) > 0) {
			throw new InvalidArgumentException((string)$errors);
		}

		$repo = $this->entityManager->getRepository(User::class);
		if ($repo->findOneBy(['email' => $dto->email]) !== null) {
			throw new DomainException('Email is already in use.');
		}

		$user = new User($dto->email);
		$hash = $this->passwordHasher->hashPassword($user, $dto->password);
		$user->setPasswordHash($hash);

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		$this->entityManager->persist(new VerificationToken($user, Uuid::v4()));
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
		if ($this->passwordHasher->isPasswordValid($user, $dto->password)) {
			throw new DomainException('New password must differ from the old one.');
		}

		$newHash = $this->passwordHasher->hashPassword($user, $dto->password);
		$user->setPasswordHash($newHash);
		$this->entityManager->flush();
	}

	public function getUserById(int $id): User
	{
		return $this->entityManager->getRepository(User::class)
			->findOneBy(['id' => $id]);
	}

	public function getToken(string $token): ?VerificationToken
	{
		return $this->entityManager->getRepository(VerificationToken::class)
			->findOneBy(['token' => $token]);
	}

	public function confirmEmail(VerificationToken $token): void
	{
		$user = $token->user;
		$user->setVerifiedDate(new DateTimeImmutable());
		$token->markAsUsed();
		$this->entityManager->flush();
	}
}
