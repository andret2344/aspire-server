<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordStartRequest
{
	#[Assert\NotBlank]
	#[Assert\Email]
	public string $email;

	#[Assert\NotBlank]
	#[Assert\Url]
	public string $url;

	public static function fromArray(array $data): self
	{
		$dto = new self();
		$dto->email = mb_strtolower(trim((string)($data['email'] ?? '')));
		$dto->url = (string)($data['url'] ?? '');
		return $dto;
	}
}
