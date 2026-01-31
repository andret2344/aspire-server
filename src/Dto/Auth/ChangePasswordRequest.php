<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordRequest
{
	#[Assert\NotBlank(message: 'validation.old-password.blank')]
	public string $oldPassword;

	#[Assert\NotBlank(message: 'validation.password.blank')]
	#[Assert\Length(min: 8, max: 255, minMessage: 'validation.password.min-length', maxMessage: 'validation.password.max-length')]
	#[Assert\NotCompromisedPassword(message: 'validation.password.compromised')]
	public string $password;

	#[Assert\NotBlank(message: 'validation.password-confirmation.blank')]
	public string $passwordConfirmation;

	public static function fromArray(array $data): self
	{
		$dto = new self();
		$dto->oldPassword = (string)($data['old_password'] ?? '');
		$dto->password = (string)($data['password'] ?? '');
		$dto->passwordConfirmation = (string)($data['password_confirmation'] ?? '');
		return $dto;
	}

	public function passwordsMatch(): bool
	{
		return $this->password === $this->passwordConfirmation;
	}
}
