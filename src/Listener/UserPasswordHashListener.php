<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, entity: User::class)]
final readonly class UserPasswordHashListener
{
	public function __construct(private UserPasswordHasherInterface $hasher) {}

	public function prePersist(User $user): void
	{
		$this->hashIfRaw($user);
	}

	public function preUpdate(User $user, PreUpdateEventArgs $args): void
	{
		if ($args->hasChangedField('password')) {
			$this->hashIfRaw($user);
		}
	}

	private function hashIfRaw(User $user): void
	{
		$password = $user->getPassword();
		if ($password === '') {
			return;
		}
		$info = password_get_info($password);
		$alreadyHashed = isset($info['algo']) && $info['algo'] !== 0;
		if (!$alreadyHashed) {
			$user->setPasswordHash($this->hasher->hashPassword($user, $password));
		}
	}
}
