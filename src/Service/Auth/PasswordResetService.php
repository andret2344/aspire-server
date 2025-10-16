<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Auth\ResetPasswordConfirmRequest;
use App\Dto\Auth\ResetPasswordStartRequest;
use App\Entity\User;
use App\Service\Mail\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use function count;

final readonly class PasswordResetService
{
	public function __construct(private EntityManagerInterface       $entityManager,
								private ResetPasswordHelperInterface $resetPasswordHelper,
								private EmailService                 $emailService,
								private ValidatorInterface           $validator) {}

	/**
	 * @throws ResetPasswordExceptionInterface
	 */
	public function start(ResetPasswordStartRequest $dto): void
	{
		$errors = $this->validator->validate($dto);
		if (count($errors) > 0) {
			return;
		}

		/** @var User|null $user */
		$user = $this->entityManager->getRepository(User::class)
			->findOneBy(['email' => $dto->email]);
		if (!$user) {
			return;
		}

		$resetToken = $this->resetPasswordHelper->generateResetToken($user);

		$url = rtrim($dto->url, '/');
		$link = sprintf('%s/%s', $url, $resetToken->getToken());

		$this->emailService->send(
			from: 'aspire@aspireapp.online',
			to: $user->getEmail(),
			subject: 'Reset your Aspire password',
			template: 'emails/password_reset_email.html.twig',
			context: ['reset_password_url' => $link]);
	}

	/**
	 * @throws ResetPasswordExceptionInterface
	 */
	public function confirm(ResetPasswordConfirmRequest $dto): void
	{
		$errors = $this->validator->validate($dto);
		if (count($errors) > 0) {
			throw new InvalidArgumentException((string)$errors);
		}
		if (!$dto->passwordsMatch()) {
			throw new DomainException('Passwords do not match.');
		}

		$user = $this->resetPasswordHelper->validateTokenAndFetchUser($dto->token);

		$this->resetPasswordHelper->removeResetRequest($dto->token);

		// ustaw nowe hasło
		$user->setPlainPassword($dto->password);
		$this->entityManager->flush();

		$this->emailService->send(
			from: 'aspire@aspireapp.online',
			to: $user->getEmail(),
			subject: 'Password reset successfully',
			template: 'emails/password_reset_successful_confirmation.html.twig');
	}
}
