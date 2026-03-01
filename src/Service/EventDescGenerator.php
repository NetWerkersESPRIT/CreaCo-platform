<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class EventDescGenerator
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $groqApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $groqApiKey;
    }

    public function generate(string $eventName): string
    {
        if (empty($eventName)) {
            return 'Please provide an event name first.';
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional event organizer. Write a short, engaging description for an event based on its name. STRICT LIMIT: Maximum 255 characters. Return ONLY the description text, nothing else.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Generate a description (max 255 chars) for this event name: $eventName",
                        ],
                    ],
                    'max_tokens' => 100,
                ],
            ]);

            $data = $response->toArray();
            $description = $data['choices'][0]['message']['content'] ?? 'No description generated.';

            // Safety truncation for the 255 character DB limit
            if (strlen($description) > 250) {
                $description = substr($description, 0, 247) . '...';
            }

            return $description;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
