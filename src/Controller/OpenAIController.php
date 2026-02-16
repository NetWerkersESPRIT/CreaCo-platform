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
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (empty($content)) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }

        $quiz = $this->openAiService->generateQuiz($content);

        return new JsonResponse(['quiz' => $quiz]);
    }
}
