<?php

namespace App\Discovery;

final readonly class DevDiscoveryProvider implements DiscoveryProviderInterface
{
	public function getDiscoveryData(): array
	{
		return [
			'backend' => 'http://localhost:8080',
			'frontend' => 'http://localhost:3005',
		];
	}
}
