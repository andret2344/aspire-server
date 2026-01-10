<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
	use ResetPasswordRequestTrait;

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private(set) ?int $id = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private(set) User $user;

	public function __construct(object $user, string $selector, string $hashedToken, DateTimeInterface $expiresAt)
	{
		if (!$user instanceof User) {
			throw new InvalidArgumentException(sprintf('Expected instance of %s, got %s', User::class, get_debug_type($user)));
		}

		$this->user = $user;
		$this->initialize($expiresAt, $selector, $hashedToken);
	}

	public function getUser(): object
	{
		return $this->user;
	}
}
