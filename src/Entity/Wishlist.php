<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_wishlist_uuid', columns: ['uuid'])]
class Wishlist implements JsonSerializable
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private ?int $id = null;

	#[ORM\Column(type: 'uuid', unique: true)]
	private Uuid $uuid;

	#[ORM\Column(type: Types::STRING, length: 255)]
	#[Assert\NotBlank]
	#[Assert\Length(max: 255)]
	private string $name;

	#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
	private ?DateTimeImmutable $isDeleted = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private User $user;

	#[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
	private ?string $accessCode = null;

	#[ORM\OneToMany(targetEntity: WishlistItem::class, mappedBy: 'wishlist', cascade: ['remove'], orphanRemoval: true)]
	private Collection $items;

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

	public function getAccessCode(): ?string
	{
		return $this->accessCode;
	}

	public function setAccessCode(string $raw): void
	{
		if ($raw !== '') {
			$this->accessCode = $raw;
			return;
		}

		$this->accessCode = null;
		foreach ($this->items->filter(fn(WishlistItem $item) => $item->isHidden()) as $item) {
			$item->unhide();
		}
	}

	public function checkAccessCode(string $accessCode): bool
	{
		if ($this->accessCode === null) {
			return false;
		}
		return $accessCode === $this->accessCode;
	}

	public function getItems(): Collection
	{
		return $this->items;
	}

	public function addItem(WishlistItem $item): void
	{
		if (!$this->items->contains($item)) {
			$this->items->add($item);
			$item->attachTo($this);
		}
	}

	public function removeItem(WishlistItem $item): void
	{
		$this->items->removeElement($item);
	}

	public function __toString(): string
	{
		return $this->name;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'uuid' => $this->uuid,
			'items' => $this->items->toArray(),
			'has_password' => $this->accessCode !== null,
		];
	}
}
