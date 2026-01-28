<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\VerificationToken;
use App\Repository\VerificationTokenRepository;
use App\Service\VerificationTokenService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class VerificationTokenServiceTest extends TestCase
{
	public function testRecreateTokenExpiresExistingToken(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$tokenRepository = $this->createMock(VerificationTokenRepository::class);
		$user = new User('test@example.com');

		$existingToken = new VerificationToken($user, 'oldhash');

		$tokenRepository->expects($this->once())
			->method('findActiveTokenByUser')
			->with($user)
			->willReturn($existingToken);

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->isInstanceOf(VerificationToken::class));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new VerificationTokenService($entityManager, $tokenRepository);
		$plainToken = $service->recreateToken($user);

		$this->assertIsString($plainToken);
		$this->assertSame(64, strlen($plainToken));
		$this->assertTrue($existingToken->isExpired());
	}

	public function testRecreateTokenWithoutExistingToken(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$tokenRepository = $this->createMock(VerificationTokenRepository::class);
		$user = new User('test@example.com');

		$tokenRepository->expects($this->once())
			->method('findActiveTokenByUser')
			->with($user)
			->willReturn(null);

		$entityManager->expects($this->once())
			->method('persist')
			->with($this->isInstanceOf(VerificationToken::class));

		$entityManager->expects($this->once())
			->method('flush');

		$service = new VerificationTokenService($entityManager, $tokenRepository);
		$plainToken = $service->recreateToken($user);

		$this->assertIsString($plainToken);
		$this->assertSame(64, strlen($plainToken));
	}

	public function testGetToken(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$tokenRepository = $this->createMock(VerificationTokenRepository::class);
		$user = new User('test@example.com');

		$plainToken = str_repeat('a', 64);
		$hashedToken = hash('sha256', $plainToken);

		$verificationToken = new VerificationToken($user, $hashedToken);

		$tokenRepository->expects($this->once())
			->method('findByHash')
			->with($hashedToken)
			->willReturn($verificationToken);

		$service = new VerificationTokenService($entityManager, $tokenRepository);
		$result = $service->getToken($plainToken);

		$this->assertSame($verificationToken, $result);
	}

	public function testGetTokenNotFound(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$tokenRepository = $this->createMock(VerificationTokenRepository::class);

		$plainToken = str_repeat('b', 64);
		$hashedToken = hash('sha256', $plainToken);

		$tokenRepository->expects($this->once())
			->method('findByHash')
			->with($hashedToken)
			->willReturn(null);

		$service = new VerificationTokenService($entityManager, $tokenRepository);
		$result = $service->getToken($plainToken);

		$this->assertNull($result);
	}

	public function testRecreateTokenGeneratesUniqueTokens(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$tokenRepository = $this->createMock(VerificationTokenRepository::class);
		$user = new User('test@example.com');

		$tokenRepository->method('findActiveTokenByUser')
			->willReturn(null);

		$entityManager->method('persist');
		$entityManager->method('flush');

		$service = new VerificationTokenService($entityManager, $tokenRepository);
		$token1 = $service->recreateToken($user);
		$token2 = $service->recreateToken($user);

		$this->assertNotSame($token1, $token2);
	}
}
