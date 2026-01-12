<?php

namespace App\Entity;

use App\Repository\VerificationTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerificationTokenRepository::class)]
class VerificationToken
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private(set) ?int $id = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private(set) User $user;

	#[ORM\Column(length: 64, unique: true)]
	private(set) string $hash;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
	private(set) DateTimeImmutable $expiresAt;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
	private(set) ?DateTimeImmutable $usedAt = null;

	public function __construct(User $user, string $hash)
	{
		$this->user = $user;
		$this->hash = $hash;
		$this->expiresAt = new DateTimeImmutable('+15 minutes');
	}

	public function expire(): void
	{
		$this->expiresAt = new DateTimeImmutable();
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
}
