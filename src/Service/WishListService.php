<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\WishList;
use App\Entity\WishListItem;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class WishListService
{
	public function __construct(private EntityManagerInterface $entityManager) {}

	public function findPublicByUuid(string $uuid): ?WishList
	{
		return $this->entityManager->getRepository(WishList::class)
			->findOneBy(['uuid' => $uuid, 'isDeleted' => null]);
	}

	public function listFor(User $user): array
	{
		return $this->entityManager->getRepository(WishList::class)
			->findBy(['user' => $user, 'isDeleted' => null], ['id' => 'ASC']);
	}

	public function create(User $user, string $name, ?string $accessCode = null): WishList
	{
		$wishList = new WishList($user, $name);
		if ($accessCode !== null) {
			$wishList->setAccessCodeRaw($accessCode);
		}
		$this->entityManager->persist($wishList);
		$this->entityManager->flush();

		return $wishList;
	}

	public function getOwned(User $user, int $id): ?WishList
	{
		return $this->entityManager->getRepository(WishList::class)
			->findOneBy(['id' => $id, 'user' => $user, 'isDeleted' => null]);
	}

	public function update(WishList $wishList, ?string $name, bool $hasAccessCodeKey, ?string $accessCode): WishList
	{
		if ($name !== null) {
			$wishList->rename($name);
		}
		if ($hasAccessCodeKey) {
			$wishList->setAccessCodeRaw($accessCode ?? '');
		}
		$this->entityManager->flush();

		return $wishList;
	}

	public function softDelete(WishList $wishList): void
	{
		$wishList->markDeleted(new DateTimeImmutable());
		$this->entityManager->flush();
	}

	public function setAccessCode(WishList $wishList, string $accessCode): void
	{
		$wishList->setAccessCodeRaw($accessCode);
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

		return $this->entityManager->getRepository(WishListItem::class)
			->findBy(['wishlist' => $wishList, 'hidden' => true], ['id' => 'ASC']);
	}
}
