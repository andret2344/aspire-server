<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\VerificationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VerificationTokenRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, VerificationToken::class);
	}

	public function findActiveTokenByUser(User $user): ?VerificationToken
	{
		return $this->createQueryBuilder('t')
			->where('t.user = :user')
			->andWhere('t.usedAt IS NULL')
			->andWhere('t.expiresAt > :now')
			->setParameter('user', $user)
			->setParameter('now', new \DateTimeImmutable())
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
	}

	public function findByHash(string $hash): ?VerificationToken
	{
		return $this->createQueryBuilder('t')
			->where('t.hash = :hash')
			->setParameter('hash', $hash)
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
	}
}
