<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private ?int $id = null;

	#[ORM\Column(type: Types::STRING, length: 255, unique: true)]
	#[Assert\NotBlank]
	#[Assert\Email]
	#[Assert\Length(max: 255)]
	private string $email;

	#[ORM\Column(type: Types::STRING, length: 255)]
	private string $password;

	#[ORM\Column(type: Types::JSON)]
	private array $roles = [];

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
	private DateTimeImmutable $joinedDate;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
	private ?DateTimeImmutable $verifiedDate;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
	private ?DateTimeImmutable $lastLogin = null;

	#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
	private bool $isStaff = false;

	public function __construct(string $email)
	{
		$this->email = mb_strtolower(trim($email));
		$this->password = '$argon2id$v=19$m=65536,t=4,p=1$placeholder$placeholder';
		$this->joinedDate = new DateTimeImmutable();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): void
	{
		$this->email = mb_strtolower(trim($email));
	}

	public function getUserIdentifier(): string
	{
		return $this->getEmail();
	}

	public function getRoles(): array
	{
		$roles = $this->roles;
		$roles[] = 'ROLE_USER';
		if ($this->isStaff) {
			$roles[] = 'ROLE_ADMIN';
		}
		return array_values(array_unique($roles));
	}

	public function setRoles(array $roles): void
	{
		$this->roles = array_values(array_unique($roles));
	}

	public function isStaff(): bool
	{
		return $this->isStaff;
	}

	public function setStaff(bool $staff): void
	{
		$this->isStaff = $staff;
	}

	public function getJoinedDate(): DateTimeImmutable
	{
		return $this->joinedDate;
	}

	public function setJoinedDate(DateTimeImmutable $joinedDate): void
	{
		$this->joinedDate = $joinedDate;
	}

	public function getVerifiedDate(): ?DateTimeImmutable
	{
		return $this->verifiedDate;
	}

	public function setVerifiedDate(?DateTimeImmutable $verifiedDate): void
	{
		$this->verifiedDate = $verifiedDate;
	}

	public function getLastLogin(): ?DateTimeImmutable
	{
		return $this->lastLogin;
	}

	public function setLastLogin(?DateTimeImmutable $at): void
	{
		$this->lastLogin = $at;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPasswordHash(string $hash): void
	{
		$this->password = $hash;
	}

	public function eraseCredentials(): void
	{
		// nothing to erase
	}

	public function __toString(): string
	{
		return $this->email;
	}
}
