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

        try {
            $response = $this->http->request('GET', self::API_URL, [
                'headers' => [
                    'X-API-Key' => $this->sentimentApiKey,
                    'Accept' => 'application/json',
                ],
                'query' => ['text' => $text],
                'timeout' => 5
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API returned status ' . $response->getStatusCode());
            }

            $data = $response->toArray();
            $this->logger->info("SentimentService Result", ['data' => $data]);

            if (($data['status'] ?? '') === 'ok' || ($data['status'] ?? '') === 'success') {
                $sentimentData = $data['data'] ?? [];
                
                // ApiVerve can return 'sentimentText' or 'label'
                // Some versions return a string directly in 'sentiment'
                $sentimentText = strtolower(
                    $sentimentData['sentimentText'] ?? 
                    $sentimentData['label'] ?? 
                    (is_string($sentimentData['sentiment'] ?? null) ? $sentimentData['sentiment'] : 'neutral')
                );
                
                // Score detection
                $score = (float)(
                    $sentimentData['normalizedScore'] ?? 
                    $sentimentData['score'] ?? 
                    $sentimentData['comparative'] ?? 
                    0.0
                );

                $label = 'NEUTRAL';
                if (str_contains($sentimentText, 'negative') || $score < -0.1) {
                    $label = 'NEGATIVE';
                    // The UI needs a positive score > 0.4 to trigger the warning
                    $score = abs($score) > 0 ? abs($score) : 0.75;
                } elseif (str_contains($sentimentText, 'positive') || $score > 0.1) {
                    $label = 'POSITIVE';
                    $score = abs($score);
                }

                return [
                    'label' => $label,
                    'score' => $score,
                    'error' => null
                ];
            }

            throw new \Exception('API status failure');
        } catch (\Throwable $e) {
            $this->logger->error("SentimentService Error: " . $e->getMessage());

            // --- EMERGENCY KEYWORD FALLBACK ---
            // If the API fails, we check for obvious negative words to still show the warning
            $angryWords = ['sick', 'exhausting', 'tired', 'hate', 'terrible', 'frustrated', 'stupid', 'bad', 'worst', 'crisis'];
            foreach ($angryWords as $word) {
                if (stripos($text, $word) !== false) {
                    return [
                        'label' => 'NEGATIVE',
                        'score' => 0.9,
                        'error' => 'Keyword Fallback: ' . $e->getMessage()
                    ];
                }
            }

            return ['label' => 'NEUTRAL', 'score' => 0, 'error' => $e->getMessage()];
        }
    }
}
