<?php

namespace App\Tests\Discovery;

use App\Discovery\DevDiscoveryProvider;
use PHPUnit\Framework\TestCase;

class DevDiscoveryProviderTest extends TestCase
{
	public function testGetDiscoveryData(): void
	{
		$provider = new DevDiscoveryProvider();
		$data = $provider->getDiscoveryData();

		$this->assertIsArray($data);
		$this->assertArrayHasKey('backend', $data);
		$this->assertArrayHasKey('frontend', $data);
		$this->assertSame('http://localhost:8080', $data['backend']);
		$this->assertSame('http://localhost:3005', $data['frontend']);
	}

	public function testGetDiscoveryDataReturnsConsistentResults(): void
	{
		$provider = new DevDiscoveryProvider();
		$data1 = $provider->getDiscoveryData();
		$data2 = $provider->getDiscoveryData();

		$this->assertSame($data1, $data2);
	}
}
