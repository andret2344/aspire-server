<?php

declare(strict_types=1);

namespace App\Service;

use App\Discovery\DiscoveryProviderInterface;

final readonly class DiscoveryService
{
	public function __construct(private DiscoveryProviderInterface $provider) {}

	public function getDiscoveryData(): array
	{
		return $this->provider->getDiscoveryData();
	}
}
