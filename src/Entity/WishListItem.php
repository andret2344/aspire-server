<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'wishlist_items')]
class WishListItem implements JsonSerializable
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private ?int $id = null;

	#[ORM\Column(type: Types::STRING, length: 255)]
	#[Assert\NotBlank]
	#[Assert\Length(max: 255)]
	private string $name;

	#[ORM\Column(type: Types::TEXT)]
	private string $description;

	/** 1..5 jak w Django (default 1). */
	#[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
	#[Assert\Range(min: 1, max: 5)]
	private int $priority;

	/** Autor pozycji. On delete: CASCADE. */
	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private User $user;

	/**
	 * Powiązana lista życzeń. On delete: NO ACTION (DO_NOTHING).
	 * Jeśli chcesz zablokować usunięcie listy, zostaw NO ACTION;
	 * jeśli jednak chcesz „osierocić” – rozważ SET NULL.
	 */
	#[ORM\ManyToOne(targetEntity: WishList::class, inversedBy: 'items')]
	#[ORM\JoinColumn(nullable: false, onDelete: 'NO ACTION')]
	private WishList $wishlist;

	#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
	private bool $hidden = false;

	public function __construct(User $user, WishList $wishlist, string $name, string $description, int $priority = 1)
	{
		$this->user = $user;
		$this->wishlist = $wishlist;
		$this->name = $name;
		$this->description = $description;
		$this->priority = $priority;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function __toString(): string
	{
		return $this->name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function rename(string $name): void
	{
		$this->name = $name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function changeDescription(string $text): void
	{
		$this->description = $text;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	public function setPriority(int $priority): void
	{
		if ($priority < 1 || $priority > 5) {
			throw new \InvalidArgumentException('Priority must be between 1 and 5.');
		}
		$this->priority = $priority;
	}

	public function isHidden(): bool
	{
		return $this->hidden;
	}

	public function hide(): void
	{
		$this->hidden = true;
	}

	public function unhide(): void
	{
		$this->hidden = false;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function getWishlist(): WishList
	{
		return $this->wishlist;
	}

	/** Używane z poziomu WishList::addItem() */
	public function attachTo(WishList $wishlist): void
	{
		$this->wishlist = $wishlist;
	}

	/** Używane z poziomu WishList::removeItem() */
	public function detachWishlist(): void
	{
		// pozostawiamy wymagane FK; jeśli chcesz pozwolić na null, zmień join na nullable: true
		// i tutaj ustaw $this->wishlist = null;
	}

	public function jsonSerialize(): mixed
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'description' => $this->description,
			'priority' => $this->priority,
			'wishlist' => $this->wishlist->getId(),
			'hidden' => $this->hidden,
		];
	}
}
