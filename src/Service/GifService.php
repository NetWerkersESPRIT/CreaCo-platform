<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GifService
{
    private const BASE_URL = 'https://api.klipy.co/v2';
    
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['KLIPY_API_KEY'] ?? '';
    }

    public function searchGifs(string $query): array
    {
        $url = self::BASE_URL . '/gifs/search';
        return $this->fetchGifs($url, ['q' => $query, 'per_page' => 21]);
    }

    public function getTrendingGifs(): array
    {
        $url = self::BASE_URL . '/gifs/trending';
        return $this->fetchGifs($url, ['per_page' => 21]);
    }

    private function fetchGifs(string $url, array $queryData = []): array
    {
        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_')) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => $queryData,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey
                ],
                'timeout' => 15
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $this->parseKlipyData($data);
            }
        } catch (\Exception $e) {
            // Log or handle error appropriately
        }

        return [];
    }

    private function parseKlipyData(array $data): array
    {
        $urls = [];
        $items = $data['data'] ?? [];

        foreach ($items as $item) {
            // Priority 1: Klipy structure file -> md -> gif -> url
            if (isset($item['file']['md']['gif']['url'])) {
                $urls[] = $item['file']['md']['gif']['url'];
                continue;
            }

            // Priority 2: Alternative Klipy structure file -> gif -> url
            if (isset($item['file']['gif']['url'])) {
                $urls[] = $item['file']['gif']['url'];
                continue;
            }

            // Priority 3: Tenor-like structure media_formats -> tinygif -> url
            if (isset($item['media_formats']['tinygif']['url'])) {
                $urls[] = $item['media_formats']['tinygif']['url'];
                continue;
            } elseif (isset($item['media_formats']['gif']['url'])) {
                $urls[] = $item['media_formats']['gif']['url'];
                continue;
            }

            // Priority 4: Direct URL
            if (isset($item['url']) && str_contains($item['url'], '.gif')) {
                $urls[] = $item['url'];
            }
        }

        return $urls;
    }
}
