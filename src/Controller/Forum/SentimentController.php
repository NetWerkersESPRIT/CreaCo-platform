<?php

namespace App\Controller\Forum;

use App\Service\SentimentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to handle real-time sentiment analysis AJAX requests.
 */
class SentimentController extends AbstractController
{
    #[Route('/forum/analyze-sentiment', name: 'forum_analyze_sentiment', methods: ['POST'])]
    public function analyze(Request $request, SentimentService $sentimentService, \Psr\Log\LoggerInterface $logger): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $text = $payload['text'] ?? '';

        $logger->info('CalmBot Controller: Received text for analysis', ['length' => strlen($text)]);

        if (empty($text)) {
            return new JsonResponse(['label' => 'NEUTRAL', 'score' => 0], 400);
        }

        $result = $sentimentService->analyze($text);
        
        $logger->info('CalmBot Controller: Analysis result', $result);

        return new JsonResponse($result);
    }
}
