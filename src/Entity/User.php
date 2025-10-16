<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Email is already in use.')]
#[ORM\HasLifecycleCallbacks]
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

	/**
	 * Zawsze przechowujemy HASH (argon2id). Ustawienie raw hasła przez setPlainPassword().
	 */
	#[ORM\Column(type: Types::STRING, length: 255)]
	private string $password;

	#[ORM\Column(type: Types::JSON)]
	private array $roles = []; // zawsze minimum ROLE_USER (patrz getRoles)

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
	private \DateTimeImmutable $joinedDate;

	#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
	private ?\DateTime $lastLogin = null;

	#[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
	private bool $isActive = true;

	#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
	private bool $isStaff = false;

	// --- transient (nie w DB) ---
	private ?string $plainPassword = null;
	private bool $passwordIsRaw = false;

	public function __construct(string $email)
	{
		$this->email = mb_strtolower(trim($email));
		$this->password = '$argon2id$v=19$m=65536,t=4,p=1$2R1hYXJnb24yMWRwbGFjZWhvbGRlcg$1QpA0WmJcQm7c7o7pQf0eg'; // nieważny placeholder
		$this->joinedDate = new \DateTimeImmutable();
	}

	// ---------------- getters/setters ----------------

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

	public function isActive(): bool
	{
		return $this->isActive;
	}

	public function setActive(bool $active): void
	{
		$this->isActive = $active;
	}

	public function isStaff(): bool
	{
		return $this->isStaff;
	}

	public function setStaff(bool $staff): void
	{
		$this->isStaff = $staff;
	}

	public function getJoinedDate(): \DateTimeImmutable
	{
		return $this->joinedDate;
	}

	public function setJoinedDate(\DateTimeImmutable $at): void
	{
		$this->joinedDate = $at;
	}

	public function getLastLogin(): ?\DateTime
	{
		return $this->lastLogin;
	}

	public function setLastLogin(?\DateTime $at): void
	{
		$this->lastLogin = $at;
	}

	// PasswordAuthenticatedUserInterface
	public function getPassword(): string
	{
		return $this->password;
	}

	/** Używaj TYLKO, jeśli już masz HASH (np. migracja). */
	public function setPasswordHash(string $hash): void
	{
		$this->password = $hash;
	}

	/**
	 * Ustaw RAW hasło; zostanie zhashowane w prePersist/preUpdate (argon2id).
	 * Pusty string spowoduje błąd walidacji przy flushu (zależnie od polityki) — tu nie wymuszam.
	 */
	public function setPlainPassword(?string $raw): void
	{
		$this->plainPassword = $raw;
		if ($raw !== null) {
			$this->password = $raw;
			$this->passwordIsRaw = true;
		}
	}

	public function eraseCredentials(): void
	{
		$this->plainPassword = null;
	}

	// ---------------- field hooks ----------------

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
	public function hashPasswordIfNeeded(): void
	{
		if ($this->passwordIsRaw && $this->password !== '') {
			$info = password_get_info($this->password);
			$alreadyHashed = isset($info['algo']) && $info['algo'] !== 0;
			if (!$alreadyHashed) {
				$this->password = password_hash($this->password, PASSWORD_ARGON2ID);
			}
			$this->passwordIsRaw = false;
		}
	}

	public function __toString(): string
	{
		return $this->email;
	}
}
