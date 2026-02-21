<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProfanityFilterService
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $apiKey,
    ) {}

    /**
     * @return array{isProfane:bool, filteredText:string, profaneWords:int|null, raw:array}
     */
    public function check(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [
                'isProfane' => false,
                'filteredText' => $text,
                'profaneWords' => null,
                'raw' => [],
            ];
        }

        // APIVerve pattern: https://api.apiverve.com/v1/{endpoint} with X-API-Key header
        $resp = $this->http->request('POST', 'https://api.apiverve.com/v1/profanityfilter', [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ],
            'json' => [
                'text' => $text,
                // Depending on APIVerve options you may have: "mask": true / "replaceWith": "*"
                // If your dashboard docs show a specific field, use it.
            ],
            'timeout' => 8,
        ]);

        $data = $resp->toArray(false);

        // Typical APIVerve response contains a "data" object (confirm in your tool response)
        $d = $data['data'] ?? [];

        return [
            'isProfane' => (bool)($d['isProfane'] ?? false),
            'filteredText' => (string)($d['filteredText'] ?? $text),
            'profaneWords' => isset($d['profaneWords']) ? (int)$d['profaneWords'] : null,
            'raw' => $data,
        ];
    }
}