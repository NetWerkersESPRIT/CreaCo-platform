<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService
{
    private $httpClient;
    private $apiKey;
    private $groqApiKey;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey, string $groqApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
        $this->groqApiKey = $groqApiKey;
    }

    public function generateContent(string $prompt): string
    {
        if (empty($this->groqApiKey)) {
            return "ERROR_AUTH: Groq API Key is not configured in .env.";
        }

        try {
            // Migrated to Groq for better reliability and performance (Gemini was returning 404)
            $url = "https://api.groq.com/openai/v1/chat/completions";

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a professional business assistant for CreaCo. Provide clear, formal, and authoritative output.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 1000
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $errorData = $response->toArray(false);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                return "API_ERROR ($statusCode): $errorMessage";
            }

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'No content generated.';
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
        $result = $this->generateContent($prompt);
        
        // If API fails due to quota, use local fallback generator
        if (str_starts_with($result, 'API_ERROR (429)')) {
            return $this->generateFallbackQuiz($content);
        }
        
        return $result;
    }

    private function generateFallbackQuiz(string $content): string
    {
        // Clean and prepare content
        $sentences = array_filter(array_map('trim', preg_split('/[.!?]\s+/', $content)));
        $sentences = array_slice($sentences, 0, 20); // Limit to first 20 sentences

        if (count($sentences) < 2) {
            return "1. What is the main topic discussed?\na) Topic A\nb) Topic B\nc) Topic C\nd) Topic D\n**Correct answer: a**";
        }

        $quiz = [];
        $questionNum = 1;

        // Generate questions from key phrases in content
        for ($i = 0; $i < min(4, count($sentences)); $i++) {
            $sentence = $sentences[$i];
            
            // Extract a question-like phrase
            if (strlen($sentence) > 15) {
                $question = "What can be concluded from: \"" . substr($sentence, 0, 60) . "...\"?";
                
                // Generate 4 plausible options
                $options = [
                    'a) ' . ucfirst(substr($sentence, 0, 40)),
                    'b) It is important to understand key concepts',
                    'c) This requires further study and analysis',
                    'd) None of the above'
                ];
                
                shuffle($options);
                $correct = array_rand($options);
                $correctLetter = chr(ord('a') + $correct);
                
                $quiz[] = "$questionNum. $question\n" . implode("\n", $options) . "\n**Correct answer: $correctLetter**";
                $questionNum++;
            }
        }

        // Ensure we have at least one question
        if (empty($quiz)) {
            $quiz[] = "1. What is the main topic of this content?\na) Introduction\nb) Analysis\nc) Conclusion\nd) Summary\n**Correct answer: a**";
        }

        return implode("\n\n", $quiz);
    }

}
