<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class UpdateLastLoginListener
{
	public function __construct(private EntityManagerInterface $entityManager) {}

	public function __invoke(LoginSuccessEvent $event): void
	{
		$user = $event->getUser();

		if (!$user instanceof User) {
			return;
		}

		$user->setLastLogin(new DateTimeImmutable());

		$this->entityManager->flush();
	}
}
