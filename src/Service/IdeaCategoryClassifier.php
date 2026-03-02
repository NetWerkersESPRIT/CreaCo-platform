<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IdeaCategoryClassifier
{
    public function __construct(
        private HttpClientInterface $client,
        private string $groqApiKey
    ) {
    }

    /**
     * Given the new idea's data and a list of existing categories,
     * return the best-fit category name using AI.
     *
     * @param string   $title            The idea title
     * @param string   $description      The idea description
     * @param string   $proposedCategory The category entered by the user
     * @param string[] $existingCategories Distinct categories already in the DB
     * @return string  The resolved category
     */
    public function classify(
        string $title,
        string $description,
        string $proposedCategory,
        array $existingCategories
    ): string {
        // If there are no existing categories, just clean up the proposed one
        $categoryList = empty($existingCategories)
            ? ''
            : implode(', ', $existingCategories);

        $systemPrompt = <<<PROMPT
You are a category normalization assistant for a collaborative idea platform.
Your task is to assign the most appropriate category to an idea based on its content.

Rules:
1. If the proposed category closely matches one of the existing categories (semantically or literally), return EXACTLY that existing category name.
2. If no existing category fits, return the proposed category as-is OR a cleaner, more professional version of it.
3. Never return more than one category.
4. Return ONLY the category name — no explanation, no punctuation, no quotes.
PROMPT;

        $userContent = "Idea Title: {$title}\n"
            . "Idea Description: {$description}\n"
            . "Proposed Category: {$proposedCategory}\n"
            . ($categoryList ? "Existing categories to consider: [{$categoryList}]" : '(No existing categories yet)');

        try {
            $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_tokens' => 20,
                    'temperature' => 0.1,
                ],
            ]);

            $data = $response->toArray(false);

            if (isset($data['error'])) {
                // Fall back to proposed category on API error
                return $proposedCategory;
            }

            $result = trim($data['choices'][0]['message']['content'] ?? '');

            // Safety: if response is empty or too long, fall back
            if (empty($result) || strlen($result) > 100) {
                return $proposedCategory;
            }

            return $result;
        } catch (\Exception $e) {
            // On any network/parse exception, fall back gracefully
            return $proposedCategory;
        }
    }
}
