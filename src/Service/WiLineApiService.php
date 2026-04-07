<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

class WiLineApiService {
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $apiUser,
        private readonly string $apiKey,
        private string $apiUrl,
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
    }

    private function getToken(): string {
        return $this->cache->get('wi_line_token', function (ItemInterface $item) {
            $response = $this->httpClient->request(Request::METHOD_POST, "{$this->apiUrl}/auth", [
                'body' => [
                    'user' => $this->apiUser,
                    'api_key' => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();

            if (!isset($data['status']) || $data['status'] !== true || !isset($data['token'])) {
                throw new \RuntimeException('Échec de l\'authentification à l\'API Wi-Line.');
            }

            if (isset($data['exp'])) {
                $item->expiresAt((new \DateTimeImmutable())->setTimestamp($data['exp'] - 30));
            } else {
                $item->expiresAfter(3600);
            }
            return $data['token'];
        });
    }

    /**
     * Get the list of laundries from the Wi-Line API
     * @return array
     */
    public function getLaundriesList(): array
    {
        return $this->cache->get('centrals_map_data', function (ItemInterface $item) {
            $item->expiresAfter(60);
            $token = $this->getToken();
            $response = $this->httpClient->request(Request::METHOD_GET, "{$this->apiUrl}/laundry_map/centrales", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]);
            $raw = $response->toArray();
            $list = \is_array($raw) ? $raw : [];

            return array_map(
                static fn(array $central): array => [
                    'id' => (string) ($central['serial'] ?? ''),
                    'name' => (string) ($central['name'] ?? ''),
                    'link' => (string) ($central['link'] ?? ''),
                ],
                $list
            );
        });
    }

    /**
     * Get the details of a laundry from the Wi-Line API
     * @param string $serial The id of the laundry
     * @return array
     */
    public function getLaundryDetails(string $serial): array
    {
        return $this->cache->get("laundry_details_{$serial}", function (ItemInterface $item) use ($serial) {
            $item->expiresAfter(60);
            $token = $this->getToken();
            $response = $this->httpClient->request(Request::METHOD_GET, "{$this->apiUrl}/laundry_map/centrales/{$serial}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]);
            return $response->toArray();
        });
    }
}