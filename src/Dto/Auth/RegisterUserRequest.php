<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserRequest
{
	#[Assert\NotBlank]
	#[Assert\Email]
	#[Assert\Length(max: 255)]
	public string $email;

	#[Assert\NotBlank]
	#[Assert\Length(min: 8, max: 255)]
	#[Assert\NotCompromisedPassword]
	public string $password;

	public static function fromArray(array $data): self
	{
		$dto = new self();
		$dto->email = mb_strtolower(trim((string)($data['email'] ?? '')));
		$dto->password = (string)($data['password'] ?? '');
		return $dto;
	}
}
