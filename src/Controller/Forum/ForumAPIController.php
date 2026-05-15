<?php

namespace App\Controller\Forum;

use App\Service\ForumAIService;
use App\Service\GifService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/forum', name: 'api_forum_')]
class ForumAPIController extends AbstractController
{
    private ForumAIService $aiService;
    private GifService $gifService;

    public function __construct(ForumAIService $aiService, GifService $gifService)
    {
        $this->aiService = $aiService;
        $this->gifService = $gifService;
    }

    #[Route('/ai/explain', name: 'ai_explain', methods: ['POST'])]
    public function explainPost(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (empty(trim($content))) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }

        $response = $this->aiService->getAIResponse($content, 'explain');
        
        return new JsonResponse(['result' => $response]);
    }

    #[Route('/ai/solution', name: 'ai_solution', methods: ['POST'])]
    public function suggestSolution(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (empty(trim($content))) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }

        $response = $this->aiService->getAIResponse($content, 'solution');
        
        return new JsonResponse(['result' => $response]);
    }

    #[Route('/gifs/trending', name: 'gifs_trending', methods: ['GET'])]
    public function getTrendingGifs(): JsonResponse
    {
        $gifs = $this->gifService->getTrendingGifs();
        return new JsonResponse(['gifs' => $gifs]);
    }

    #[Route('/gifs/search', name: 'gifs_search', methods: ['GET'])]
    public function searchGifs(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (empty(trim($query))) {
            return $this->getTrendingGifs();
        }

        $gifs = $this->gifService->searchGifs($query);
        return new JsonResponse(['gifs' => $gifs]);
    }
}
