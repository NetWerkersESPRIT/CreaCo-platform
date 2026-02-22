<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MissionDescGenerator
{
    public function __construct(
        private HttpClientInterface $client,
        private string $groqApiKey
    ) {
    }

    public function generate(string $title, string $ideaTitle, ?string $ideaDescription = null, ?string $ideaCategory = null): string
    {
        try {
            $ideaContext = "Idea Title: $ideaTitle" .
                ($ideaDescription ? ". Idea Description: $ideaDescription" : "") .
                ($ideaCategory ? ". Category: $ideaCategory" : "");

            $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => "You are a content creator who has a team of editors that work under him. Generate a short description that is of interest to your team mentioning the type of work they need to do to complete the mission, based on the provided idea, its description, its category and the name of the mission. MAX 1000 chars total. Emphasize on the tasks that need to be done. to complete the mission. Be precise and informative of the steps and requirements. Refrain from repeating the mission and idea titles and the idea's category."],
                        ['role' => 'user', 'content' => "Mission Title: $title. Based on: $ideaContext"]
                    ],
                    'max_tokens' => 150,
                ],
            ]);
            $data = $response->toArray(false);

            if (isset($data['error'])) {
                return 'AI Error: ' . ($data['error']['message'] ?? 'Unknown error');
            }

            $text = $data['choices'][0]['message']['content'] ?? 'Mission failed to generate.';

            // Ensure we never break the 1000 DB limit
            if (strlen($text) > 1000) {
                return substr($text, 0, 997) . '...';
            }
            return $text;
        } catch (\Exception $e) {
            return 'Generation Exception: ' . $e->getMessage();
        }
    }
}