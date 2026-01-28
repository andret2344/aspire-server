<?php

namespace App\Tests\Listener;

use App\Entity\User;
use App\Listener\UpdateLastLoginListener;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class UpdateLastLoginListenerTest extends TestCase
{
	public function testInvokeUpdatesLastLoginForUser(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$event = $this->createMock(LoginSuccessEvent::class);

		$event->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$entityManager->expects($this->once())
			->method('flush');

		$listener = new UpdateLastLoginListener($entityManager);

		$beforeLogin = new DateTimeImmutable();
		$listener($event);
		$afterLogin = new DateTimeImmutable();

		$lastLogin = $user->getLastLogin();
		$this->assertNotNull($lastLogin);
		$this->assertGreaterThanOrEqual($beforeLogin, $lastLogin);
		$this->assertLessThanOrEqual($afterLogin, $lastLogin);
	}

	public function testInvokeDoesNotUpdateForNonUserInstance(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$nonUser = $this->createStub(UserInterface::class);
		$event = $this->createMock(LoginSuccessEvent::class);

		$event->expects($this->once())
			->method('getUser')
			->willReturn($nonUser);

		$entityManager->expects($this->never())
			->method('flush');

		$listener = new UpdateLastLoginListener($entityManager);
		$listener($event);
	}

	public function testInvokeUpdatesExistingLastLogin(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$user = new User('test@example.com');
		$event = $this->createMock(LoginSuccessEvent::class);

		$oldLastLogin = new DateTimeImmutable('-1 day');
		$user->setLastLogin($oldLastLogin);

		$event->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$entityManager->expects($this->once())
			->method('flush');

		$listener = new UpdateLastLoginListener($entityManager);
		$listener($event);

		$newLastLogin = $user->getLastLogin();
		$this->assertNotNull($newLastLogin);
		$this->assertGreaterThan($oldLastLogin, $newLastLogin);
	}
}
