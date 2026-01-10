<?php

namespace App\Discovery;

interface DiscoveryProviderInterface
{
	public function getDiscoveryData(): array;
}
