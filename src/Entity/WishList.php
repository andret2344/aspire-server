<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'wishlists')]
#[ORM\UniqueConstraint(name: 'uniq_wishlist_uuid', columns: ['uuid'])]
#[ORM\HasLifecycleCallbacks]
class WishList implements JsonSerializable
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private ?int $id = null;

	/** Public-share identifier (read-only view link). */
	#[ORM\Column(type: 'uuid', unique: true)]
	private Uuid $uuid;

	#[ORM\Column(type: Types::STRING, length: 255)]
	#[Assert\NotBlank]
	#[Assert\Length(max: 255)]
	private string $name;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
	private ?\DateTimeImmutable $isDeleted = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private User $user;

	#[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
	private ?string $accessCode = null;

	/** Flaga „ma hasło” (szybkie sprawdzenie, bez ładowania pola). */
	#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
	private bool $hasPassword = false;

	/** Pozycje listy (nie kasujemy przy usunięciu listy – DO_NOTHING w dziecku). */
	#[ORM\OneToMany(targetEntity: WishListItem::class, mappedBy: 'wishlist', cascade: ['remove'])]
	private Collection $items;

	/** Flaga pomocnicza: czy accessCode wymaga hashowania. */
	private bool $accessCodeIsRaw = false;

	public function __construct(User $user, string $name)
	{
		$this->uuid = Uuid::v7();
		$this->user = $user;
		$this->name = $name;
		$this->items = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUuid(): Uuid
	{
		return $this->uuid;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function rename(string $name): void
	{
		$this->name = $name;
	}

	public function getIsDeleted(): ?\DateTimeImmutable
	{
		return $this->isDeleted;
	}

	public function markDeleted(?\DateTimeImmutable $at = null): void
	{
		$this->isDeleted = $at ?? new \DateTimeImmutable();
	}

	public function restore(): void
	{
		$this->isDeleted = null;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function transferTo(User $newOwner): void
	{
		$this->user = $newOwner;
	}

	public function hasPassword(): bool
	{
		return $this->hasPassword;
	}

	public function getAccessCodeHash(): ?string
	{
		return $this->accessCode;
	}

	public function setAccessCodeRaw(string $raw): void
	{
		if ($raw === '') {
			$this->accessCode = null;
			$this->hasPassword = false;
			$this->accessCodeIsRaw = false;

			foreach ($this->items->filter(fn(WishListItem $item) => $item->isHidden()) as $item) {
				$item->unhide();
			}
			return;
		}

		$this->accessCode = $raw;
		$this->hasPassword = true;
		$this->accessCodeIsRaw = true;
	}

	/** Sprawdza raw kod względem hasha z bazy. */
	public function checkAccessCode(string $raw): bool
	{
		if ($this->accessCode === null) {
			return false;
		}
		return password_verify($raw, $this->accessCode);
	}

	/** @return Collection<int, WishListItem> */
	public function getItems(): Collection
	{
		return $this->items;
	}

	public function addItem(WishListItem $item): void
	{
		if (!$this->items->contains($item)) {
			$this->items->add($item);
			$item->attachTo($this);
		}
	}

	public function removeItem(WishListItem $item): void
	{
		if ($this->items->removeElement($item) && $item->getWishlist() === $this) {
			$item->detachWishlist();
		}
	}

	public function __toString(): string
	{
		return $this->name;
	}

	// ----------------- Field hooks (lifecycle) -----------------

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
	public function hashAccessCodeIfNeeded(): void
	{
		if ($this->accessCodeIsRaw && $this->accessCode !== null) {
			// Jeżeli wygląda na już zahashowane – nic nie rób (np. migracja danych).
			$info = password_get_info($this->accessCode);
			$alreadyHashed = isset($info['algo']) && $info['algo'] !== 0;

			if (!$alreadyHashed) {
				$this->accessCode = password_hash($this->accessCode, PASSWORD_ARGON2ID);
			}
			$this->accessCodeIsRaw = false;
		}
	}

	public function jsonSerialize(): mixed
	{
		$visibleItems = $this->items
			->filter(fn(WishListItem $item) => !$item->isHidden());

		return [
			'id' => $this->id,
			'name' => $this->name,
			'uuid' => $this->uuid,
			'items' => array_values($visibleItems->toArray()),
			'has_password' => $this->hasPassword,
		];
	}
}
