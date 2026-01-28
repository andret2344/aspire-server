<?php

namespace App\Tests\Discovery;

use App\Discovery\ProdDiscoveryProvider;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProdDiscoveryProviderTest extends TestCase
{
	public function testGetDiscoveryDataSuccessful(): void
	{
		$cache = $this->createMock(CacheInterface::class);
		$httpClient = $this->createMock(HttpClientInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$response->method('toArray')
			->willReturn([
				'data' => [
					'backend' => 'https://api.example.com',
					'frontend' => 'https://app.example.com',
				],
			]);

		$httpClient->method('request')
			->with('GET', 'https://discovery.andret.eu/', [
				'query' => ['uuid' => 'test-token'],
				'timeout' => 5.0,
			])
			->willReturn($response);

		$cache->method('get')
			->willReturnCallback(function (string $key, callable $callback) {
				$item = $this->createMock(ItemInterface::class);
				$item->expects($this->once())
					->method('expiresAfter')
					->with(86400);

				return $callback($item);
			});

		$provider = new ProdDiscoveryProvider($cache, $httpClient, 'test-token');
		$data = $provider->getDiscoveryData();

		$this->assertIsArray($data);
		$this->assertArrayHasKey('backend', $data);
		$this->assertArrayHasKey('frontend', $data);
		$this->assertSame('https://api.example.com', $data['backend']);
		$this->assertSame('https://app.example.com', $data['frontend']);
	}

	public function testGetDiscoveryDataWithMissingFields(): void
	{
		$cache = $this->createMock(CacheInterface::class);
		$httpClient = $this->createMock(HttpClientInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$response->method('toArray')
			->willReturn(['data' => []]);

		$httpClient->method('request')
			->willReturn($response);

		$cache->method('get')
			->willReturnCallback(function (string $key, callable $callback) {
				$item = $this->createMock(ItemInterface::class);
				return $callback($item);
			});

		$provider = new ProdDiscoveryProvider($cache, $httpClient, 'test-token');
		$data = $provider->getDiscoveryData();

		$this->assertIsArray($data);
		$this->assertNull($data['backend']);
		$this->assertNull($data['frontend']);
	}

	public function testGetDiscoveryDataWithHttpException(): void
	{
		$cache = $this->createMock(CacheInterface::class);
		$httpClient = $this->createMock(HttpClientInterface::class);

		$httpClient->method('request')
			->willThrowException(new Exception('Network error'));

		$cache->method('get')
			->willReturnCallback(function (string $key, callable $callback) {
				$item = $this->createMock(ItemInterface::class);
				$item->method('expiresAfter')
					->willReturnSelf();

				return $callback($item);
			});

		$provider = new ProdDiscoveryProvider($cache, $httpClient, 'test-token');
		$data = $provider->getDiscoveryData();

		$this->assertIsArray($data);
		$this->assertNull($data['backend']);
		$this->assertNull($data['frontend']);
	}

	public function testGetDiscoveryDataUsesCacheKey(): void
	{
		$cache = $this->createMock(CacheInterface::class);
		$httpClient = $this->createMock(HttpClientInterface::class);

		$expectedKey = 'discovery_' . hash('sha256', 'test-token');

		$cache->expects($this->once())
			->method('get')
			->with($expectedKey)
			->willReturnCallback(function (string $key, callable $callback) {
				$item = $this->createMock(ItemInterface::class);
				$response = $this->createMock(ResponseInterface::class);
				$response->method('toArray')
					->willReturn(['data' => []]);
				return $callback($item);
			});

		$httpClient->method('request')
			->willReturn($this->createMock(ResponseInterface::class));

		$provider = new ProdDiscoveryProvider($cache, $httpClient, 'test-token');
		$provider->getDiscoveryData();
	}

	public function testGetDiscoveryDataWithInvalidResponseStructure(): void
	{
		$cache = $this->createMock(CacheInterface::class);
		$httpClient = $this->createMock(HttpClientInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$response->method('toArray')
			->willReturn([]);

		$httpClient->method('request')
			->willReturn($response);

		$cache->method('get')
			->willReturnCallback(function (string $key, callable $callback) {
				$item = $this->createMock(ItemInterface::class);
				return $callback($item);
			});

		$provider = new ProdDiscoveryProvider($cache, $httpClient, 'test-token');
		$data = $provider->getDiscoveryData();

		$this->assertIsArray($data);
		$this->assertNull($data['backend']);
		$this->assertNull($data['frontend']);
	}
}
