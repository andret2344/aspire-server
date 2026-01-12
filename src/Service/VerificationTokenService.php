<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\VerificationToken;
use App\Repository\VerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class VerificationTokenService
{
	public function __construct(private EntityManagerInterface      $entityManager,
								private VerificationTokenRepository $tokenRepository) {}

	public function recreateToken(User $user): string
	{
		$this->tokenRepository->findActiveTokenByUser($user)
			?->expire();
		$plainToken = bin2hex(random_bytes(32));
		$token = new VerificationToken($user, $this->hashToken($plainToken));
		$this->entityManager->persist($token);
		$this->entityManager->flush();
		return $plainToken;
	}

	public function getToken(string $plainToken): ?VerificationToken
	{
		return $this->tokenRepository->findByHash($this->hashToken($plainToken));
	}

	private function hashToken(string $token): string
	{
		return hash('sha256', $token);
	}
}
