<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class WishlistItem implements JsonSerializable
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: Types::INTEGER)]
	private(set) ?int $id = null;

	#[ORM\Column(type: Types::STRING, length: 255)]
	#[Assert\NotBlank]
	#[Assert\Length(max: 255)]
	public string $name {
		get => $this->name;
		set {
			$name = trim($value);
			if (!$name || mb_strlen($name) > 255) {
				throw new InvalidArgumentException('Name must be between 1 and 255 characters.');
			}
			$this->name = $name;
		}
	}

	#[ORM\Column(type: Types::TEXT)]
	public string $description {
		get => $this->description;
		set => $this->description = $value;
	}

	#[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
	#[Assert\Range(min: 0, max: 3)]
	public int $priority {
		get => $this->priority;
		set {
			if ($value < 0 || $value > 3) {
				throw new InvalidArgumentException('Priority must be between 0 and 3.');
			}
			$this->priority = $value;
		}
	}

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	private(set) User $user;

	#[ORM\ManyToOne(targetEntity: Wishlist::class, inversedBy: 'items')]
	#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
	public Wishlist $wishlist {
		get => $this->wishlist;
		set => $this->wishlist = $value;
	}

	#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
	public bool $hidden = false {
		get => $this->hidden;
		set => $this->hidden = $value;
	}

	public function __construct(User $user, Wishlist $wishlist, string $name, string $description, int $priority = 0, bool $hidden = false)
	{
		$this->user = $user;
		$this->wishlist = $wishlist;
		$this->name = $name;
		$this->description = $description;
		$this->priority = $priority;
		$this->hidden = $hidden;
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
