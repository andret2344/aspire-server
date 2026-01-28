<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use App\Service\WishlistItemService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use DomainException;
use PHPUnit\Framework\TestCase;

class WishlistItemServiceTest extends TestCase
{
	public function testListForWishlist(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');

		$items = [
			new WishlistItem($user, $wishlist, 'Item 1', 'Desc 1', 0),
			new WishlistItem($user, $wishlist, 'Item 2', 'Desc 2', 1),
		];

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(WishlistItem::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findBy')
			->with(['wishlist' => $wishlist], ['id' => 'ASC'])
			->willReturn($items);

		$service = new WishlistItemService($entityManager);
		$result = $service->listForWishlist($wishlist);

		$this->assertSame($items, $result);
	}

	public function testGetOwnedItem(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$item = new WishlistItem($user, $wishlist, 'Item', 'Desc', 0);

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(WishlistItem::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['id' => 1, 'wishlist' => $wishlist])
			->willReturn($item);

		$service = new WishlistItemService($entityManager);
		$result = $service->getOwnedItem($wishlist, 1);

		$this->assertSame($item, $result);
	}

	public function testCreate(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->isInstanceOf(WishlistItem::class));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistItemService($entityManager);
		$item = $service->create($user, $wishlist, 'New Item', 'Description', 1, false);

		$this->assertInstanceOf(WishlistItem::class, $item);
		$this->assertSame('New Item', $item->name);
		$this->assertSame('Description', $item->description);
		$this->assertSame(1, $item->priority);
		$this->assertFalse($item->hidden);
	}

	public function testCreateHiddenItemThrowsExceptionWithoutAccessCode(): void
	{
		$entityManager = $this->createStub(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');

		$service = new WishlistItemService($entityManager);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Wishlist must have an access code to allow hidden items.');

		$service->create($user, $wishlist, 'Hidden Item', 'Description', 1, true);
	}

	public function testCreateHiddenItemWithAccessCode(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$wishlist->setAccessCode('secret');

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->callback(function (WishlistItem $item) {
				$this->assertTrue($item->hidden);
				return true;
			}));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistItemService($entityManager);
		$item = $service->create($user, $wishlist, 'Hidden Item', 'Description', 1, true);

		$this->assertTrue($item->hidden);
	}

	public function testUpdate(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$item = new WishlistItem($user, $wishlist, 'Old Name', 'Old Desc', 0);

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistItemService($entityManager);
		$result = $service->update($item, $wishlist, 'New Name', 'New Desc', 2, null);

		$this->assertSame($item, $result);
		$this->assertSame('New Name', $item->name);
		$this->assertSame('New Desc', $item->description);
		$this->assertSame(2, $item->priority);
	}

	public function testUpdatePartial(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$item = new WishlistItem($user, $wishlist, 'Old Name', 'Old Desc', 0);

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistItemService($entityManager);
		$result = $service->update($item, $wishlist, 'New Name', null, null, null);

		$this->assertSame('New Name', $item->name);
		$this->assertSame('Old Desc', $item->description);
		$this->assertSame(0, $item->priority);
	}

	public function testUpdateHiddenThrowsExceptionWithoutAccessCode(): void
	{
		$entityManager = $this->createStub(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$item = new WishlistItem($user, $wishlist, 'Item', 'Desc', 0);

		$service = new WishlistItemService($entityManager);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Wishlist must have an access code to allow hidden items.');

		$service->update($item, $wishlist, null, null, null, true);
	}

	public function testUpdateHiddenWithAccessCode(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$wishlist->setAccessCode('secret');
		$item = new WishlistItem($user, $wishlist, 'Item', 'Desc', 0);

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistItemService($entityManager);
		$result = $service->update($item, $wishlist, null, null, null, true);

		$this->assertTrue($item->hidden);
	}

	public function testDelete(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$item = new WishlistItem($user, $wishlist, 'Item', 'Desc', 0);

		$entityManager->expects($this->once())
			->method('remove')
			->with($item);

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistItemService($entityManager);
		$service->delete($item);
	}
}
