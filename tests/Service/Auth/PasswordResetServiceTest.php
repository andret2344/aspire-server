<?php

namespace App\Tests\Service\Auth;

use App\Dto\Auth\ResetPasswordConfirmRequest;
use App\Dto\Auth\ResetPasswordStartRequest;
use App\Entity\User;
use App\Service\Auth\PasswordResetService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class PasswordResetServiceTest extends TestCase
{
	public function testStartWithValidationErrors(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
		$emailService = $this->createMock(EmailService::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$dto = new ResetPasswordStartRequest();
		$dto->email = 'invalid';
		$dto->url = 'not-a-url';

		$violations = $this->createMock(ConstraintViolationList::class);
		$violations->method('count')
			->willReturn(2);

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn($violations);

		$emailService->expects($this->never())
			->method('send');

		$service = new PasswordResetService($entityManager, $resetPasswordHelper, $emailService, $validator);
		$service->start($dto);
	}

	public function testStartWithNonExistentUser(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
		$emailService = $this->createMock(EmailService::class);
		$validator = $this->createMock(ValidatorInterface::class);
		$repository = $this->createMock(EntityRepository::class);

		$dto = new ResetPasswordStartRequest();
		$dto->email = 'nonexistent@example.com';
		$dto->url = 'https://example.com/reset';

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
			->with(['email' => 'nonexistent@example.com'])
			->willReturn(null);

		$emailService->expects($this->never())
			->method('send');

		$service = new PasswordResetService($entityManager, $resetPasswordHelper, $emailService, $validator);
		$service->start($dto);
	}


	public function testConfirmWithValidationErrors(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
		$emailService = $this->createMock(EmailService::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$dto = new ResetPasswordConfirmRequest();
		$dto->token = 'token';
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

		$service = new PasswordResetService($entityManager, $resetPasswordHelper, $emailService, $validator);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Validation error');

		$service->confirm($dto);
	}

	public function testConfirmWithPasswordMismatch(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
		$emailService = $this->createMock(EmailService::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$dto = new ResetPasswordConfirmRequest();
		$dto->token = 'token';
		$dto->password = 'password123';
		$dto->passwordConfirmation = 'different123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$service = new PasswordResetService($entityManager, $resetPasswordHelper, $emailService, $validator);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Passwords do not match.');

		$service->confirm($dto);
	}

	public function testConfirmSuccessfully(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
		$emailService = $this->createMock(EmailService::class);
		$validator = $this->createMock(ValidatorInterface::class);

		$user = new User('test@example.com');
		$dto = new ResetPasswordConfirmRequest();
		$dto->token = 'reset-token-123';
		$dto->password = 'newpassword123';
		$dto->passwordConfirmation = 'newpassword123';

		$validator->expects($this->once())
			->method('validate')
			->with($dto)
			->willReturn(new ConstraintViolationList());

		$resetPasswordHelper->expects($this->once())
			->method('validateTokenAndFetchUser')
			->with('reset-token-123')
			->willReturn($user);

		$resetPasswordHelper->expects($this->once())
			->method('removeResetRequest')
			->with('reset-token-123');

		$entityManager->expects($this->once())
			->method('flush');

		$emailService->expects($this->once())
			->method('send')
			->with(
				'aspire@aspireapp.online',
				'test@example.com',
				'Password reset successfully',
				'password_reset_confirmation.html.twig',
				['user' => $user]
			);

		$service = new PasswordResetService($entityManager, $resetPasswordHelper, $emailService, $validator);
		$service->confirm($dto);

		// Verify the password was set (it will be hashed by the listener on flush)
		$this->assertSame('newpassword123', $user->getPassword());
	}

}
