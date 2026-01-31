<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserRequest
{
	#[Assert\NotBlank(message: 'validation.email.blank')]
	#[Assert\Email(message: 'validation.email.invalid')]
	#[Assert\Length(max: 255, maxMessage: 'validation.email.max-length')]
	public string $email;

	#[Assert\NotBlank(message: 'validation.password.blank')]
	#[Assert\Length(min: 8, max: 255, minMessage: 'validation.password.min-length', maxMessage: 'validation.password.max-length')]
	#[Assert\NotCompromisedPassword(message: 'validation.password.compromised')]
	public string $password;

	public static function fromArray(array $data): self
	{
		$dto = new self();
		$dto->email = mb_strtolower(trim((string)($data['email'] ?? '')));
		$dto->password = (string)($data['password'] ?? '');
		return $dto;
	}
}
