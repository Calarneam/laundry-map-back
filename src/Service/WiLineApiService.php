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
        private readonly string $apiUrl,
        private readonly string $apiUser,
        private readonly string $apiKey,
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
    }

    private function getToken(): string {
        return $this->cache->get('wi_line_token', function (ItemInterface $item) {
            $response = $this->httpClient->request(Request::METHOD_POST, $this->apiUrl . '/auth', [
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
}