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
        $prompt = $this->buildAnalysisPrompt($request);

        try {
            $response = $this->openAIService->generateContent($prompt);

            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonData = json_decode($matches[0], true);
                if ($jsonData) {
                    $request->setAiClarityScore($jsonData['clarity_score'] ?? 0);
                    $request->setAiBudgetRealismScore($jsonData['budget_realism_score'] ?? 0);
                    $request->setAiTimelineFeasibilityScore($jsonData['timeline_feasibility_score'] ?? 0);
                    $request->setAiFlags($jsonData['flags'] ?? []);
                    $request->setAiSuccessScore($request->getOverallAcceptancePrediction());

                    return [
                        'likelihood' => $request->getOverallAcceptancePrediction(),
                        'reasoning' => $jsonData['reasoning'] ?? '',
                        'clarity' => $jsonData['clarity_score'] ?? 0,
                        'budget' => $jsonData['budget_realism_score'] ?? 0,
                        'timeline' => $jsonData['timeline_feasibility_score'] ?? 0,
                        'flags' => $jsonData['flags'] ?? []
                    ];
                }
            }

            $this->logger->warning('CollaborationAIService: Failed to parse AI response as JSON. Raw response: ' . $response);

            return [
                'likelihood' => 50,
                'reasoning' => '',
                'clarity' => 50,
                'budget' => 50,
                'timeline' => 50,
                'flags' => []
            ];

        } catch (\Exception $e) {
            $this->logger->error('CollaborationAIService: AI error. ' . $e->getMessage());
            return [
                'likelihood' => 0,
                'reasoning' => '',
                'clarity' => 0,
                'budget' => 0,
                'timeline' => 0,
                'flags' => []
            ];
        }
    }

    private function buildAnalysisPrompt(CollabRequest $request): string
    {
        $creator = $request->getCreator();
        $collaborator = $request->getCollaborator();

        $prompt = "You are an expert AI business analyst for the CreaCo platform. 
Your task is to analyze a collaboration proposal and provide deep intelligence for the administrator's Oversight Hub.

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

### ANALYSIS REQUIREMENTS:
1. **Clarity Score (0-100)**: How well-defined is the proposal?
2. **Budget Realism Score (0-100)**: Does the budget match the deliverables and effort?
3. **Timeline Feasibility Score (0-100)**: Is the period sufficient for the work described?
4. **Automated Flags**: Specifically look for:
   - \"CONTRADICTORY_CLAUSES\": Conflicting dates, amounts, or terms.
   - \"MISSING_DELIVERABLES\": Description mentions tasks not listed in deliverables.
   - \"VAGUE_PAYMENT\": Unclear when or how payment occurs.
   - \"UNREALISTIC_TIMELINE\": Too short or logically impossible dates.

### OUTPUT FORMAT:
Provide your analysis in STRICT JSON format like this:
{
  \"clarity_score\": 85,
  \"budget_realism_score\": 70,
  \"timeline_feasibility_score\": 90,
  \"reasoning\": \"Comprehensive overview...\",
  \"flags\": [
    {\"type\": \"VAGUE_PAYMENT\", \"detail\": \"Mentions 'later' instead of specific milestones.\"},
    {\"type\": \"MISSING_DELIVERABLES\", \"detail\": \"Mentions video editing but it's not in the deliverable list.\"}
  ]
}

Analyze the data and provide ONLY the JSON object.";

        return $prompt;
    }
}
