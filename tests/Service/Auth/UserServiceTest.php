<?php

namespace App\Tests\Service\Auth;

use App\Dto\Auth\ChangePasswordRequest;
use App\Dto\Auth\RegisterUserRequest;
use App\Entity\User;
use App\Entity\VerificationToken;
use App\Service\Auth\UserService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserServiceTest extends TestCase
{
	public function testRegisterWithValidationErrors(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$dto = new RegisterUserRequest();
		$dto->email = 'invalid';
		$dto->password = 'short';

		$violation1 = new ConstraintViolation(
			'Invalid email format',
			null,
			[],
			$dto,
			'email',
			'invalid',
			null,
			'EMAIL_INVALID'
		);
		$violation2 = new ConstraintViolation(
			'Password must be at least 8 characters',
			null,
			[],
			$dto,
			'password',
			'short',
			null,
			'PASSWORD_TOO_SHORT'
		);

		$violations = new ConstraintViolationList([$violation1, $violation2]);

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn($violations);

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(InvalidArgumentException::class);
		$expectedJson = json_encode([
			['field' => 'email', 'error' => 'Invalid email format'],
			['field' => 'password', 'error' => 'Password must be at least 8 characters']
		]);
		$this->expectExceptionMessage($expectedJson);

		$service->register($dto);
	}

	public function testRegisterWithValidationErrorsWithoutCodes(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$dto = new RegisterUserRequest();
		$dto->email = 'invalid';
		$dto->password = 'short';

		$violation1 = new ConstraintViolation(
			'Invalid email format',
			null,
			[],
			$dto,
			'email',
			'invalid',
			null,
			'EMAIL_INVALID'
		);
		$violation2 = new ConstraintViolation(
			'Password too short',
			null,
			[],
			$dto,
			'password',
			'short',
			null,
			null  // No error code - this violation should be filtered out
		);

		$violations = new ConstraintViolationList([$violation1, $violation2]);

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn($violations);

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(InvalidArgumentException::class);
		// Only the violation with a code should be included
		$expectedJson = json_encode([
			['field' => 'email', 'error' => 'Invalid email format']
		]);
		$this->expectExceptionMessage($expectedJson);

		$service->register($dto);
	}

	public function testRegisterWithExistingEmail(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);
		$repository = $this->createMock(EntityRepository::class);

		$dto = new RegisterUserRequest();
		$dto->email = 'existing@example.com';
		$dto->password = 'password123';

		$existingUser = new User('existing@example.com');

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(User::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['email' => 'existing@example.com'])
			->willReturn($existingUser);

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Email is already in use.');

		$service->register($dto);
	}

	public function testRegisterSuccessfully(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);
		$repository = $this->createMock(EntityRepository::class);

		$dto = new RegisterUserRequest();
		$dto->email = 'new@example.com';
		$dto->password = 'password123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(User::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['email' => 'new@example.com'])
			->willReturn(null);

		$passwordHasher->expects($this->once())
			->method('hashPassword')
			->willReturn('$argon2id$hashed');

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->isInstanceOf(User::class));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new UserService($entityManager, $passwordHasher, $validator);
		$user = $service->register($dto);

		$this->assertInstanceOf(User::class, $user);
		$this->assertSame('new@example.com', $user->getEmail());
	}

	public function testChangePasswordWithValidationErrors(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$dto = new ChangePasswordRequest();
		$dto->oldPassword = 'old';
		$dto->password = 'short';
		$dto->passwordConfirmation = 'short';

		$violations = $this->createMock(ConstraintViolationList::class);
		$violations->method('count')
			->willReturn(1);
		$violations->method('__toString')
			->willReturn('Validation error');

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn($violations);

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Validation error');

		$service->changePassword($user, $dto);
	}

	public function testChangePasswordWithPasswordMismatch(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$dto = new ChangePasswordRequest();
		$dto->oldPassword = 'oldpassword123';
		$dto->password = 'newpassword123';
		$dto->passwordConfirmation = 'different123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Passwords do not match.');

		$service->changePassword($user, $dto);
	}

	public function testChangePasswordWithIncorrectOldPassword(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$dto = new ChangePasswordRequest();
		$dto->oldPassword = 'wrongpassword';
		$dto->password = 'newpassword123';
		$dto->passwordConfirmation = 'newpassword123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$passwordHasher->expects($this->once())
			->method('isPasswordValid')
			->with($user, 'wrongpassword')
			->willReturn(false);

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Incorrect old password!');

		$service->changePassword($user, $dto);
	}

	public function testChangePasswordWithSamePassword(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$dto = new ChangePasswordRequest();
		$dto->oldPassword = 'password123';
		$dto->password = 'password123';
		$dto->passwordConfirmation = 'password123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$passwordHasher->expects($this->exactly(2))
			->method('isPasswordValid')
			->willReturnCallback(function ($user, $password) {
				return $password === 'password123';
			});

		$service = new UserService($entityManager, $passwordHasher, $validator);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('New password must differ from the old one.');

		$service->changePassword($user, $dto);
	}

	public function testChangePasswordSuccessfully(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$dto = new ChangePasswordRequest();
		$dto->oldPassword = 'oldpassword123';
		$dto->password = 'newpassword123';
		$dto->passwordConfirmation = 'newpassword123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$passwordHasher->expects($this->exactly(2))
			->method('isPasswordValid')
			->willReturnCallback(function ($user, $password) {
				return $password === 'oldpassword123';
			});

		$passwordHasher->expects($this->once())
			->method('hashPassword')
			->with($user, 'newpassword123')
			->willReturn('$argon2id$newhash');

		$entityManager->expects($this->once())
			->method('flush');

		$service = new UserService($entityManager, $passwordHasher, $validator);
		$service->changePassword($user, $dto);

		$this->assertSame('$argon2id$newhash', $user->getPassword());
	}

	public function testGetUserById(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);
		$repository = $this->createMock(EntityRepository::class);

		$user = new User('test@example.com');

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(User::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['id' => 1])
			->willReturn($user);

		$service = new UserService($entityManager, $passwordHasher, $validator);
		$result = $service->getUserById(1);

		$this->assertSame($user, $result);
	}

	public function testGetUserByIdNotFound(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);
		$repository = $this->createMock(EntityRepository::class);

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(User::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['id' => 999])
			->willReturn(null);

		$service = new UserService($entityManager, $passwordHasher, $validator);
		$result = $service->getUserById(999);

		$this->assertNull($result);
	}

	public function testConfirmEmail(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$token = new VerificationToken($user, 'hash123');

		$entityManager->expects($this->once())
			->method('flush');

		$service = new UserService($entityManager, $passwordHasher, $validator);
		$service->confirmEmail($token);

		$this->assertInstanceOf(DateTimeImmutable::class, $user->getVerifiedDate());
		$this->assertTrue($token->isUsed());
	}
}
