<?php

namespace App\Controller\Collab;

use App\Service\OpenAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ai')]
class CollabAIController extends AbstractController
{
    private OpenAIService $aiService;
    private \Doctrine\ORM\EntityManagerInterface $entityManager;

    public function __construct(OpenAIService $aiService, \Doctrine\ORM\EntityManagerInterface $entityManager)
    {
        $this->aiService = $aiService;
        $this->entityManager = $entityManager;
    }

    #[Route('/rephrase', name: 'app_collab_ai_rephrase', methods: ['POST'])]
    public function rephrase(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->get('user_id')) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $context = $data['context'] ?? 'general';
        $requestId = $data['requestId'] ?? null;

        if (empty(trim($text))) {
            return new JsonResponse(['error' => 'No text provided'], 400);
        }

        $prompt = $this->getRephrasePrompt($text, $context);
        $rephrased_text = $this->aiService->generateContent($prompt);
        $hasError = str_starts_with($rephrased_text, 'Error:') || str_starts_with($rephrased_text, 'API_ERROR') || str_starts_with($rephrased_text, 'ERROR_AUTH');

        if (!$hasError && $requestId) {
            $collabRequest = $this->entityManager->getRepository(\App\Entity\CollabRequest::class)->find($requestId);
            if ($collabRequest) {
                // Tracking comparison
                $collabRequest->setAiOriginalContent($text);
                $collabRequest->setAiRephrasedContent(trim($rephrased_text));
                $collabRequest->incrementAiUsageCount();
                $this->entityManager->flush();
            }
        }

        return new JsonResponse([
            'original' => $text,
            'rephrased' => $hasError ? null : trim($rephrased_text),
            'success' => !$hasError,
            'error' => $hasError ? $rephrased_text : null
        ]);
    }

    private function getRephrasePrompt(string $text, string $context): string
    {
        $contextDesc = match ($context) {
            'description' => 'a collaboration proposal description for a creative project',
            'deliverables' => 'a list of high-quality creative deliverables',
            'payment' => 'formal payment terms and financial arrangements',
            'contract_terms' => 'the legal object and terms of a service contract',
            'clauses' => 'legal confidentiality or cancellation clauses',
            default => 'a professional business collaboration context',
        };

        return "You are a professional business consultant for CreaCo, a high-end creative collaboration platform.
Your task is to take the following rough or informal text and rephrase it into highly formal, professional, and convincing business language suitable for $contextDesc.

### GUIDELINES:
- Maintain the original meaning and core facts.
- Use sophisticated vocabulary (e.g., 'maximize impact', 'strategic alignment', 'deliverable milestones').
- Ensure the tone is authoritative yet collaborative.
- Keep the length similar or slightly more detailed for clarity.
- Output ONLY the rephrased text. No conversational filler.

### TEXT TO REPHRASE:
\"$text\"

### FORMAL REPHRASED VERSION:";
    }
}
