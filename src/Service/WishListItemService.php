<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\WishList;
use App\Entity\WishListItem;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;

final readonly class WishListItemService
{
	public function __construct(private EntityManagerInterface $entityManager) {}

	public function listForWishlist(WishList $wl): array
	{
		return $this->entityManager->getRepository(WishListItem::class)
			->findBy(['wishlist' => $wl], ['id' => 'ASC']);
	}

	public function getOwnedItem(WishList $wishList, int $id): ?WishListItem
	{
		return $this->entityManager->getRepository(WishListItem::class)
			->findOneBy(['id' => $id, 'wishlist' => $wishList]);
	}

	public function create(User $user, WishList $wishList, string $name, string $description, int $priority, bool $hidden): WishListItem
	{
		if ($name === '') {
			throw new InvalidArgumentException('Field "name" is required');
		}
		if ($priority < 1 || $priority > 5) {
			throw new InvalidArgumentException('priority_id must be between 1 and 5');
		}
		if ($hidden && !$wishList->hasPassword()) {
			throw new DomainException('Wishlist must have an access code to allow hidden items.');
		}

		$it = new WishListItem($user, $wishList, $name, $description, $priority);
		if ($hidden) {
			$it->hide();
		}

		$this->entityManager->persist($it);
		$this->entityManager->flush();

		return $it;
	}

	public function update(WishListItem $item, WishList $wishList, ?string $name, ?string $description, ?int $priority, ?bool $hidden): WishListItem
	{
		if ($name !== null) {
			$name = trim($name);
			if ($name === '') {
				throw new InvalidArgumentException('Field "name" cannot be empty');
			}
			$item->rename($name);
		}

		if ($description !== null) {
			$item->changeDescription($description);
		}

		if ($priority !== null) {
			if ($priority < 1 || $priority > 5) {
				throw new InvalidArgumentException('priority_id must be between 1 and 5');
			}
			$item->setPriority($priority);
		}

		if ($hidden !== null) {
			if ($hidden && !$wishList->hasPassword()) {
				throw new DomainException('Wishlist must have an access code to allow hidden items.');
			}
			$hidden ? $item->hide() : $item->unhide();
		}

		$this->entityManager->flush();

		return $item;
	}

	public function delete(WishListItem $it): void
	{
		$this->entityManager->remove($it);
		$this->entityManager->flush();
	}
}
