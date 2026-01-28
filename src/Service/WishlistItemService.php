<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;

final readonly class WishlistItemService
{
	public function __construct(private EntityManagerInterface $entityManager) {}

	public function listForWishlist(Wishlist $wl): array
	{
		return $this->entityManager->getRepository(WishlistItem::class)
			->findBy(['wishlist' => $wl], ['id' => 'ASC']);
	}

	public function getOwnedItem(Wishlist $wishList, int $id): ?WishlistItem
	{
		return $this->entityManager->getRepository(WishlistItem::class)
			->findOneBy(['id' => $id, 'wishlist' => $wishList]);
	}

	public function create(User $user, Wishlist $wishList, string $name, string $description, int $priority, bool $hidden): WishlistItem
	{
		if ($hidden && !$wishList->getAccessCode()) {
			throw new DomainException('Wishlist must have an access code to allow hidden items.');
		}

		$item = new WishlistItem($user, $wishList, $name, $description, $priority, $hidden);
		$this->entityManager->persist($item);
		$this->entityManager->flush();
		return $item;
	}

	public function update(WishlistItem $item, Wishlist $wishList, ?string $name, ?string $description, ?int $priority, ?bool $hidden): WishlistItem
	{
		if ($name !== null) {
			$item->name = $name;
		}

		if ($description !== null) {
			$item->description = $description;
		}

		if ($priority !== null) {
			$item->priority = $priority;
		}

		if ($hidden !== null) {
			if ($hidden && !$wishList->getAccessCode()) {
				throw new DomainException('Wishlist must have an access code to allow hidden items.');
			}
			$item->hidden = $hidden;
		}

		$this->entityManager->flush();

		return $item;
	}

	public function delete(WishlistItem $it): void
	{
		$this->entityManager->remove($it);
		$this->entityManager->flush();
	}
}
