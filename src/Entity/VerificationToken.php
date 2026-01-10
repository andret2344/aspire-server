<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class VerificationToken
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private(set) ?int $id = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private(set) User $user;

	#[ORM\Column(length: 255)]
	private(set) string $token;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
	private(set) DateTimeImmutable $expiresAt;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
	private(set) ?DateTimeImmutable $usedAt = null;

	public function __construct(User $user, Uuid $token)
	{
		$this->user = $user;
		$this->token = $token->toRfc4122();
		$this->expiresAt = new DateTimeImmutable('+15 minutes');
	}

	public function isExpired(): bool
	{
		return $this->expiresAt < new DateTimeImmutable();
	}

	public function isUsed(): bool
	{
		return $this->usedAt !== null;
	}

	public function markAsUsed(): void
	{
		$this->usedAt = new DateTimeImmutable();
	}

	public function isValid(): bool
	{
		return !$this->isExpired() && !$this->isUsed();
	}

	public function __toString(): string
	{
		return $this->token;
	}
}
