<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
    }

    public function generateContent(string $prompt): string
    {
        if (empty($this->apiKey)) {
            return "ERROR_AUTH: Gemini API Key is not configured in .env.";
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $this->apiKey;
            
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ],
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 429) {
                return 'ERROR_RATE_LIMIT: You have reached your Gemini rate limit. Please wait a moment.';
            }

            if ($statusCode === 400 || $statusCode === 401) {
                return 'ERROR_AUTH: Invalid API Key or request. Please check your .env file.';
            }

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No content generated.';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function generateDescription(string $title): string
    {
        $prompt = "Write a compelling and detailed course description for a course titled: \"$title\". The description should be professional, engaging, and highlight what students will learn. Keep it under 500 characters.";
        return $this->generateContent($prompt);
    }

    public function generateQuiz(string $content): string
    {
        $prompt = "Based on the following course content, generate 3 multiple-choice questions with answers:\n\n$content";
        return $this->generateContent($prompt);
    }
}
