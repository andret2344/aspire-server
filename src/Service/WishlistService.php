<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class WishlistService
{
	public function __construct(private EntityManagerInterface $entityManager) {}

	public function findPublicByUuid(string $uuid): ?Wishlist
	{
		return $this->entityManager->getRepository(Wishlist::class)
			->findOneBy(['uuid' => $uuid, 'isDeleted' => null]);
	}

	public function listFor(User $user): array
	{
		return $this->entityManager->getRepository(Wishlist::class)
			->findBy(['user' => $user, 'isDeleted' => null], ['id' => 'ASC']);
	}

	public function create(User $user, string $name, ?string $accessCode = null): Wishlist
	{
		$wishList = new Wishlist($user, $name);
		if ($accessCode !== null) {
			$wishList->setAccessCode($accessCode);
		}
		$this->entityManager->persist($wishList);
		$this->entityManager->flush();

		return $wishList;
	}

	public function getOwned(User $user, int $id): ?Wishlist
	{
		return $this->entityManager->getRepository(Wishlist::class)
			->findOneBy(['id' => $id, 'user' => $user, 'isDeleted' => null]);
	}

	public function update(Wishlist $wishList, ?string $name, bool $hasAccessCodeKey, ?string $accessCode = null): Wishlist
	{
		if ($name !== null) {
			$wishList->rename($name);
		}
		if ($hasAccessCodeKey) {
			$wishList->setAccessCode($accessCode);
		}
		$this->entityManager->flush();

		return $wishList;
	}

	public function softDelete(Wishlist $wishList): void
	{
		$wishList->markDeleted(new DateTimeImmutable());
		$this->entityManager->flush();
	}

	public function setAccessCode(Wishlist $wishList, ?string $accessCode): void
	{
		$wishList->setAccessCode($accessCode);
		$this->entityManager->flush();
	}

	public function hiddenItemsForUuid(string $uuid, ?string $rawAccessCode): array
	{
		$wishList = $this->findPublicByUuid($uuid);
		if (!$wishList) {
			throw new NotFoundHttpException('NOT_FOUND');
		}
		if (!$rawAccessCode || !$wishList->checkAccessCode($rawAccessCode)) {
			throw new UnauthorizedHttpException('INVALID_ACCESS_CODE');
		}

		return $this->entityManager->getRepository(WishlistItem::class)
			->findBy(['wishlist' => $wishList, 'hidden' => true], ['id' => 'ASC']);
	}
}
