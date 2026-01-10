<?php

namespace App\Discovery;

use Exception;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ProdDiscoveryProvider implements DiscoveryProviderInterface
{
	private const int TTL_OK = 86400; // 24h
	private const int TTL_FAIL = 300; // 5 min

	public function __construct(
		private CacheInterface      $cache,
		private HttpClientInterface $httpClient,
		private string              $token,
	) {}

	public function getDiscoveryData(): array
	{
		$cacheKey = 'discovery_' . hash('sha256', $this->token);

		return $this->cache->get($cacheKey, function (ItemInterface $item) {
			try {
				$item->expiresAfter(self::TTL_OK);

				$response = $this->httpClient->request('GET', 'https://discovery.andret.eu/', [
					'query' => ['uuid' => $this->token],
					'timeout' => 5.0,
				]);

				$payload = $response->toArray(false);
				$data = $payload['data'] ?? [];

				return [
					'backend' => $data['backend'] ?? null,
					'frontend' => $data['frontend'] ?? null,
				];
			} catch (Exception) {
				$item->expiresAfter(self::TTL_FAIL);

				return [
					'backend' => null,
					'frontend' => null,
				];
			}
		});
	}
}
