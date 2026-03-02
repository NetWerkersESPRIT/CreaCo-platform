<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/ai')]
class AIAssistantController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $pythonApiUrl = 'http://127.0.0.1:5000';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Ask a question about a course
     */
    #[Route('/question', name: 'app_ai_ask_question', methods: ['POST'])]
    public function askQuestion(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['question'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Question is required'
                ], 400);
            }

            $question = $data['question'];
            $courseId = $data['course_id'] ?? null;
            
            $user = $this->getUser();
            $userId = ($user instanceof \App\Entity\Users) ? $user->getId() : null;

            // Call Python AI service
            $response = $this->httpClient->request('POST', $this->pythonApiUrl . '/api/qa/ask', [
                'json' => [
                    'question' => $question,
                    'course_id' => $courseId,
                    'user_id' => $userId,
                    'top_k' => 5
                ],
                'timeout' => 30
            ]);

            $aiResponse = $response->toArray();

            return new JsonResponse([
                'success' => true,
                'answer' => $aiResponse['answer'] ?? '',
                'sources' => $aiResponse['sources'] ?? [],
                'confidence' => $aiResponse['confidence'] ?? 0
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get answer from AI service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get course summary from AI
     */
    #[Route('/course/{courseId}/summary', name: 'app_ai_course_summary', methods: ['GET'])]
    public function getCourseSummary(int $courseId): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', 
                $this->pythonApiUrl . '/api/courses/' . $courseId . '/summary',
                ['timeout' => 10]
            );

            $summary = $response->toArray();

            return new JsonResponse([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get course summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if AI service is available
     */
    #[Route('/health', name: 'app_ai_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', 
                $this->pythonApiUrl . '/health',
                ['timeout' => 5]
            );

            $data = $response->toArray();

            return new JsonResponse([
                'available' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'available' => false,
                'error' => 'AI service is not available'
            ], 503);
        }
    }
}
