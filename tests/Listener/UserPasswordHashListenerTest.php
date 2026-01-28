<?php

namespace App\Tests\Listener;

use App\Entity\User;
use App\Listener\UserPasswordHashListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHashListenerTest extends TestCase
{
	public function testPrePersistHashesRawPassword(): void
	{
		$hasher = $this->createMock(UserPasswordHasherInterface::class);
		$user = new User('test@example.com');

		// Set a raw password
		$user->setPasswordHash('rawPassword123');

		$hasher->expects($this->once())
			->method('hashPassword')
			->with($user, 'rawPassword123')
			->willReturn('$argon2id$v=19$m=65536,t=4,p=1$hashedPassword');

		$listener = new UserPasswordHashListener($hasher);
		$listener->prePersist($user);

		$this->assertSame('$argon2id$v=19$m=65536,t=4,p=1$hashedPassword', $user->getPassword());
	}

	public function testPrePersistDoesNotHashAlreadyHashedPassword(): void
	{
		$hasher = $this->createMock(UserPasswordHasherInterface::class);
		$user = new User('test@example.com');

		// Set an already hashed password
		$hashedPassword = '$argon2id$v=19$m=65536,t=4,p=1$existingHash$existingHash';
		$user->setPasswordHash($hashedPassword);

		$hasher->expects($this->never())
			->method('hashPassword');

		$listener = new UserPasswordHashListener($hasher);
		$listener->prePersist($user);

		$this->assertSame($hashedPassword, $user->getPassword());
	}

	public function testPrePersistDoesNotHashEmptyPassword(): void
	{
		$hasher = $this->createMock(UserPasswordHasherInterface::class);
		$user = new User('test@example.com');

		$user->setPasswordHash('');

		$hasher->expects($this->never())
			->method('hashPassword');

		$listener = new UserPasswordHashListener($hasher);
		$listener->prePersist($user);

		$this->assertSame('', $user->getPassword());
	}

	public function testPreUpdateHashesPasswordWhenChanged(): void
	{
		$hasher = $this->createMock(UserPasswordHasherInterface::class);
		$user = new User('test@example.com');
		$args = $this->createMock(PreUpdateEventArgs::class);

		$args->expects($this->once())
			->method('hasChangedField')
			->with('password')
			->willReturn(true);

		$user->setPasswordHash('newRawPassword');

		$hasher->expects($this->once())
			->method('hashPassword')
			->with($user, 'newRawPassword')
			->willReturn('$argon2id$v=19$m=65536,t=4,p=1$newHash');

		$listener = new UserPasswordHashListener($hasher);
		$listener->preUpdate($user, $args);

		$this->assertSame('$argon2id$v=19$m=65536,t=4,p=1$newHash', $user->getPassword());
	}

	public function testPreUpdateDoesNotHashWhenPasswordNotChanged(): void
	{
		$hasher = $this->createMock(UserPasswordHasherInterface::class);
		$user = new User('test@example.com');
		$args = $this->createMock(PreUpdateEventArgs::class);

		$args->expects($this->once())
			->method('hasChangedField')
			->with('password')
			->willReturn(false);

		$hasher->expects($this->never())
			->method('hashPassword');

		$listener = new UserPasswordHashListener($hasher);
		$listener->preUpdate($user, $args);
	}

	public function testPreUpdateDoesNotHashAlreadyHashedPassword(): void
	{
		$hasher = $this->createMock(UserPasswordHasherInterface::class);
		$user = new User('test@example.com');
		$args = $this->createMock(PreUpdateEventArgs::class);

		$hashedPassword = '$argon2id$v=19$m=65536,t=4,p=1$existingHash$existingHash';
		$user->setPasswordHash($hashedPassword);

		$args->expects($this->once())
			->method('hasChangedField')
			->with('password')
			->willReturn(true);

		$hasher->expects($this->never())
			->method('hashPassword');

		$listener = new UserPasswordHashListener($hasher);
		$listener->preUpdate($user, $args);

		$this->assertSame($hashedPassword, $user->getPassword());
	}
}
