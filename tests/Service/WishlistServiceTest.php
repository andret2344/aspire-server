<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use App\Service\WishlistService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class WishlistServiceTest extends TestCase
{
	public function testFindPublicByUuid(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test Wishlist');

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(Wishlist::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['uuid' => 'test-uuid', 'isDeleted' => null])
			->willReturn($wishlist);

		$service = new WishlistService($entityManager);
		$result = $service->findPublicByUuid('test-uuid');

		$this->assertSame($wishlist, $result);
	}

	public function testListFor(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');

		$wishlists = [
			new Wishlist($user, 'Wishlist 1'),
			new Wishlist($user, 'Wishlist 2'),
		];

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(Wishlist::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findBy')
			->with(['user' => $user, 'isDeleted' => null], ['id' => 'ASC'])
			->willReturn($wishlists);

		$service = new WishlistService($entityManager);
		$result = $service->listFor($user);

		$this->assertSame($wishlists, $result);
	}

	public function testCreate(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->isInstanceOf(Wishlist::class));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistService($entityManager);
		$wishlist = $service->create($user, 'New Wishlist');

		$this->assertInstanceOf(Wishlist::class, $wishlist);
		$this->assertSame('New Wishlist', $wishlist->getName());
	}

	public function testCreateWithAccessCode(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->callback(function (Wishlist $wishlist) {
				$this->assertSame('secret123', $wishlist->getAccessCode());
				return true;
			}));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistService($entityManager);
		$wishlist = $service->create($user, 'New Wishlist', 'secret123');

		$this->assertSame('secret123', $wishlist->getAccessCode());
	}

	public function testGetOwned(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test Wishlist');

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(Wishlist::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['id' => 1, 'user' => $user, 'isDeleted' => null])
			->willReturn($wishlist);

		$service = new WishlistService($entityManager);
		$result = $service->getOwned($user, 1);

		$this->assertSame($wishlist, $result);
	}

	public function testUpdate(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Old Name');

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistService($entityManager);
		$result = $service->update($wishlist, 'New Name', false);

		$this->assertSame($wishlist, $result);
		$this->assertSame('New Name', $wishlist->getName());
	}

	public function testUpdateWithAccessCode(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistService($entityManager);
		$result = $service->update($wishlist, null, true, 'newcode');

		$this->assertSame('newcode', $wishlist->getAccessCode());
	}

	public function testSoftDelete(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistService($entityManager);
		$service->softDelete($wishlist);

		$this->assertNotNull($wishlist->getIsDeleted());
		$this->assertInstanceOf(DateTimeImmutable::class, $wishlist->getIsDeleted());
	}

	public function testSetAccessCode(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');

		$entityManager->expects($this->once())
			->method('flush');

		$service = new WishlistService($entityManager);
		$service->setAccessCode($wishlist, 'mycode');

		$this->assertSame('mycode', $wishlist->getAccessCode());
	}

	public function testHiddenItemsForUuidThrowsNotFoundForInvalidUuid(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(Wishlist::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['uuid' => 'invalid-uuid', 'isDeleted' => null])
			->willReturn(null);

		$service = new WishlistService($entityManager);

		$this->expectException(NotFoundHttpException::class);
		$this->expectExceptionMessage('NOT_FOUND');

		$service->hiddenItemsForUuid('invalid-uuid', 'code');
	}

	public function testHiddenItemsForUuidThrowsUnauthorizedForInvalidAccessCode(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$repository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$wishlist->setAccessCode('correctcode');

		$entityManager->expects($this->once())
			->method('getRepository')
			->with(Wishlist::class)
			->willReturn($repository);

		$repository->expects($this->once())
			->method('findOneBy')
			->with(['uuid' => 'test-uuid', 'isDeleted' => null])
			->willReturn($wishlist);

		$service = new WishlistService($entityManager);

		$this->expectException(UnauthorizedHttpException::class);

		$service->hiddenItemsForUuid('test-uuid', 'wrongcode');
	}

	public function testHiddenItemsForUuidReturnsItems(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$wishlistRepository = $this->createMock(EntityRepository::class);
		$itemRepository = $this->createMock(EntityRepository::class);
		$user = new User('test@example.com');
		$wishlist = new Wishlist($user, 'Test');
		$wishlist->setAccessCode('correctcode');

		$items = [
			new WishlistItem($user, $wishlist, 'Item 1', 'Desc 1', 0),
			new WishlistItem($user, $wishlist, 'Item 2', 'Desc 2', 1),
		];

		$entityManager->expects($this->exactly(2))
			->method('getRepository')
			->willReturnCallback(function ($class) use ($wishlistRepository, $itemRepository) {
				return $class === Wishlist::class ? $wishlistRepository : $itemRepository;
			});

		$wishlistRepository->expects($this->once())
			->method('findOneBy')
			->with(['uuid' => 'test-uuid', 'isDeleted' => null])
			->willReturn($wishlist);

		$itemRepository->expects($this->once())
			->method('findBy')
			->with(['wishlist' => $wishlist, 'hidden' => true], ['id' => 'ASC'])
			->willReturn($items);

		$service = new WishlistService($entityManager);
		$result = $service->hiddenItemsForUuid('test-uuid', 'correctcode');

		$this->assertSame($items, $result);
	}
}
