<?php

namespace App\Controller;

use App\Service\OpenAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/openai')]
class OpenAIController extends AbstractController
{
    private $openAiService;

    public function __construct(OpenAIService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    #[Route('/generate-description', name: 'api_openai_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';

        if (empty($title)) {
            return new JsonResponse(['error' => 'Title is required'], 400);
        }

        $description = $this->openAiService->generateDescription($title);

        return new JsonResponse(['description' => $description]);
    }

    #[Route('/generate-quiz', name: 'api_openai_generate_quiz', methods: ['POST'])]
    public function generateQuiz(Request $request): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return new JsonResponse([
                'error' => 'Authentication required',
                'message' => 'Please log in to generate a quiz.'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (empty($content)) {
            return new JsonResponse([
                'error' => 'Content required',
                'message' => 'Please provide content to generate a quiz from.'
            ], 400);
        }

        if (strlen($content) < 50) {
            return new JsonResponse([
                'error' => 'Content too short',
                'message' => 'Content must be at least 50 characters.'
            ], 400);
        }

        try {
            $quiz = $this->openAiService->generateQuiz($content);

            if (empty($quiz)) {
                return new JsonResponse([
                    'error' => 'Generation failed',
                    'message' => 'The AI could not generate a quiz. Please try again.'
                ], 500);
            }

            if (str_starts_with($quiz, 'ERROR_RATE_LIMIT')) {
                return new JsonResponse([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please wait a moment and try again.'
                ], 429);
            }

            if (str_starts_with($quiz, 'ERROR_AUTH')) {
                return new JsonResponse([
                    'error' => 'API configuration error',
                    'message' => 'The AI service is not properly configured. Please contact support.'
                ], 500);
            }

            if (str_starts_with($quiz, 'Error:')) {
                return new JsonResponse([
                    'error' => 'AI service error',
                    'message' => $quiz
                ], 500);
            }

            return new JsonResponse([
                'quiz' => $quiz,
                'message' => 'Quiz generated successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Server error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }
}
