<?php

namespace App\Tests\Service;

use App\Discovery\DiscoveryProviderInterface;
use App\Service\DiscoveryService;
use PHPUnit\Framework\TestCase;

class DiscoveryServiceTest extends TestCase
{
	public function testGetDiscoveryData(): void
	{
		$provider = $this->createMock(DiscoveryProviderInterface::class);

		$provider->expects($this->once())
			->method('getDiscoveryData')
			->willReturn([
				'backend' => 'https://api.example.com',
				'frontend' => 'https://app.example.com',
			]);

		$service = new DiscoveryService($provider);
		$data = $service->getDiscoveryData();

		$this->assertIsArray($data);
		$this->assertSame('https://api.example.com', $data['backend']);
		$this->assertSame('https://app.example.com', $data['frontend']);
	}

	public function testGetDiscoveryDataDelegatesCalls(): void
	{
		$provider = $this->createMock(DiscoveryProviderInterface::class);

		$provider->expects($this->exactly(2))
			->method('getDiscoveryData')
			->willReturn(['backend' => 'test', 'frontend' => 'test']);

		$service = new DiscoveryService($provider);
		$service->getDiscoveryData();
		$service->getDiscoveryData();
	}
}
