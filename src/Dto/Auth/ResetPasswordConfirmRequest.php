<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordConfirmRequest
{
	#[Assert\NotBlank]
	public string $token;

	#[Assert\NotBlank]
	#[Assert\Length(min: 8, max: 255)]
	#[Assert\NotCompromisedPassword]
	public string $password;

	#[Assert\NotBlank]
	public string $passwordConfirmation;

	public static function fromArray(array $data): self
	{
		$dto = new self();
		$dto->token = (string)($data['token'] ?? '');
		$dto->password = (string)($data['password'] ?? '');
		$dto->passwordConfirmation = (string)($data['password_confirmation'] ?? '');
		return $dto;
	}

	public function passwordsMatch(): bool
	{
		return $this->password === $this->passwordConfirmation;
	}
}
