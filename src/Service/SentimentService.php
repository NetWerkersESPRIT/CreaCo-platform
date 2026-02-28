<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to analyze the sentiment of a text using APIVerve Sentiment Analysis API.
 */
class SentimentService
{
    private const API_URL = 'https://api.apiverve.com/v1/sentimentanalysis';
    private const FALLBACK_API_URL = 'https://api.apiverve.com/v1/sentiment';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly LoggerInterface $logger,
        private readonly string $sentimentApiKey
    ) {}

    /**
     * Analyzes the sentiment of the provided text.
     * 
     * @param string $text The text to analyze.
     * @return array{label: string, score: float, error: ?string}
     */
    public function analyze(string $text): array
    {
        $text = trim($text);
        if (empty($text) || strlen($text) < 10) {
            return ['label' => 'NEUTRAL', 'score' => 0.0, 'error' => null];
        }

        // --- 1. Try Main Endpoint ---
        $result = $this->callApi(self::API_URL, $text);
        
        // --- 2. Fallback to Secondary Endpoint if needed ---
        if ($result['error'] || $result['label'] === 'NEUTRAL') {
            $result = $this->callApi(self::FALLBACK_API_URL, $text);
        }

        // --- 3. Final Keyword Fallback (In case of API total blackout or quota) ---
        if ($result['error']) {
            $angryWords = ['angry', 'hate', 'stupid', 'unacceptable', 'frustrated', 'terrible', 'bad', 'worst'];
            foreach ($angryWords as $word) {
                if (stripos($text, $word) !== false) {
                    return ['label' => 'NEGATIVE', 'score' => 0.9, 'error' => 'Keyword Fallback'];
                }
            }
        }

        return $result;
    }

    private function callApi(string $url, string $text): array
    {
        try {
            $response = $this->http->request('GET', $url, [
                'headers' => [
                    'X-API-Key' => $this->sentimentApiKey,
                    'Accept' => 'application/json',
                ],
                'query' => ['text' => $text],
                'timeout' => 5
            ]);

            $data = $response->toArray();
            $this->logger->info("SentimentService: Result from $url", ['data' => $data]);

            if (($data['status'] ?? '') === 'ok' || ($data['status'] ?? '') === 'success') {
                $sentimentData = $data['data'] ?? [];
                return [
                    'label' => strtoupper($sentimentData['label'] ?? 'NEUTRAL'),
                    'score' => (float)($sentimentData['score'] ?? 0.0),
                    'error' => null
                ];
            }
            return ['label' => 'NEUTRAL', 'score' => 0, 'error' => 'API Status Failure'];
        } catch (\Throwable $e) {
            return ['label' => 'NEUTRAL', 'score' => 0, 'error' => $e->getMessage()];
        }
    }
}
