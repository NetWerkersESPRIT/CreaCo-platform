<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ForumAIService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_MODEL = 'openrouter/auto';
    
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';
    }

    public function getAIResponse(string $content, string $action): string
    {
        if (empty(trim($this->apiKey)) || str_contains($this->apiKey, 'YOUR_')) {
            return "Error: OpenRouter API key is missing or invalid.";
        }

        $prompt = $this->buildPrompt($content, $action);

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => 'http://localhost:8000',
                    'X-Title' => 'CreaCo Forum'
                ],
                'json' => [
                    'model' => self::DEFAULT_MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                return $data['choices'][0]['message']['content'] ?? "No content returned.";
            } else {
                return "API Error: " . $statusCode . "\n" . $response->getContent(false);
            }
        } catch (\Exception $e) {
            return "Request failed: " . $e->getMessage();
        }
    }

    private function buildPrompt(string $content, ?string $action): string
    {
        if (!$action) {
            return $content;
        }

        switch (strtolower($action)) {
            case 'explain':
                return "Explain the content of this forum post in very simple terms, as a single short and easy-to-read paragraph:\n" . $content;
            case 'solution':
                return "Propose only ONE short, concise, and highly effective solution or piece of advice for this forum post:\n" . $content;
            default:
                return $content;
        }
    }
}
