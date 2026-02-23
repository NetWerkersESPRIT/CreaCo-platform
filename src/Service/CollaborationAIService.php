<?php

namespace App\Service;

use App\Entity\CollabRequest;
use Psr\Log\LoggerInterface;

class CollaborationAIService
{
    private OpenAIService $openAIService;
    private LoggerInterface $logger;

    public function __construct(OpenAIService $openAIService, LoggerInterface $logger)
    {
        $this->openAIService = $openAIService;
        $this->logger = $logger;
    }

    /**
     * Predicts the likelihood of a collaboration request being accepted.
     * Returns an array with 'likelihood' (0-100) and 'reasoning' (text).
     */
    public function predictStatus(CollabRequest $request): array
    {
        $prompt = $this->buildStatusPredictionPrompt($request);

        try {
            $response = $this->openAIService->generateContent($prompt);

            // Expected format from AI: "JSON: {"likelihood": 85, "reasoning": "..."}"
            // We'll try to extract JSON from the response
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonData = json_decode($matches[0], true);
                if ($jsonData && isset($jsonData['likelihood'], $jsonData['reasoning'])) {
                    return [
                        'likelihood' => (int) $jsonData['likelihood'],
                        'reasoning' => $jsonData['reasoning']
                    ];
                }
            }

            $this->logger->warning('CollaborationAIService: Failed to parse AI response as JSON. Raw response: ' . $response);

            // Fallback: If not JSON or error occurs, return a neutral empty message
            return [
                'likelihood' => 50,
                'reasoning' => ''
            ];

        } catch (\Exception $e) {
            $this->logger->error('CollaborationAIService: AI error. ' . $e->getMessage());
            return [
                'likelihood' => 0,
                'reasoning' => ''
            ];
        }
    }

    private function buildStatusPredictionPrompt(CollabRequest $request): string
    {
        $creator = $request->getCreator();
        $collaborator = $request->getCollaborator();

        $prompt = "You are an expert AI business analyst for the CreaCo platform. 
Your task is to predict the likelihood of a collaboration proposal being accepted by the manager and successfully leading to a signed contract.

### COLLABORATION DETAILS:
- Title: \"{$request->getTitle()}\"
- Description: \"{$request->getDescription()}\"
- Budget: {$request->getBudget()} DT
- Deliverables: \"{$request->getDeliverables()}\"
- Payment Terms: \"{$request->getPaymentTerms()}\"
- Duration: " . ($request->getStartDate() ? $request->getStartDate()->format('d/m/Y') : 'N/A') . " to " . ($request->getEndDate() ? $request->getEndDate()->format('d/m/Y') : 'N/A') . "

### CONTEXT:
- Creator User: " . ($creator ? $creator->getUsername() : 'Guest') . "
- Target Partner: " . ($collaborator ? $collaborator->getCompanyName() : 'N/A') . "

### OUTPUT FORMAT:
Provide your analysis in STRICT JSON format like this:
{
  \"likelihood\": 85,
  \"reasoning\": \"Detailed but concise explanation of why this proposal is likely (or not) to succeed, focusing on budget realism, description clarity, and timeline feasibility.\"
}

Analyze the data and provide ONLY the JSON object.";

        return $prompt;
    }
}
