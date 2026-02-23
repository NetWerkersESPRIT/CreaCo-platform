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
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey;

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

            if ($statusCode !== 200) {
                $errorData = $response->toArray(false);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                return "API_ERROR ($statusCode): $errorMessage";
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
        $prompt = "Based on the following educational content, create a quiz with 3-5 multiple choice questions.

IMPORTANT: Format each question exactly like this:
1. [Question text here]
a) [Option A]
b) [Option B]
c) [Option C]
d) [Option D]
**Correct answer: [letter]**

Content:
$content

Generate the quiz now:";
        return $this->generateContent($prompt);
    }
}
