<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordStartRequest
{
	#[Assert\NotBlank(message: 'validation.email.blank')]
	#[Assert\Email(message: 'validation.email.invalid')]
	#[Assert\Length(max: 255, maxMessage: 'validation.email.max-length')]
	public string $email;

	#[Assert\NotBlank(message: 'validation.url.blank')]
	#[Assert\Url(message: 'validation.url.invalid')]
	public string $url;

	public static function fromArray(array $data): self
	{
		$dto = new self();
		$dto->email = mb_strtolower(trim((string)($data['email'] ?? '')));
		$dto->url = (string)($data['url'] ?? '');
		return $dto;
	}
}
