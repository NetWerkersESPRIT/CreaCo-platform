<?php

namespace App\Controller\Collab\Admin;

use App\Repository\CollabRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/collab/ai-oversight')]
class AIOversightHubController extends AbstractController
{
    #[Route('/', name: 'admin_collab_ai_oversight', methods: ['GET'])]
    public function index(CollabRequestRepository $repo, Request $request): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException("Strictly restricted to Agency Administrators.");
        }

        $allRequests = $repo->findBy([], ['createdAt' => 'DESC']);

        $totalUsage = 0;
        $avgClarity = 0;
        $avgBudget = 0;
        $avgTimeline = 0;
        $requestsWithAI = 0;
        $flaggedRequests = [];

        foreach ($allRequests as $req) {
            $totalUsage += $req->getAiUsageCount();
            if ($req->getAiClarityScore() !== null) {
                $avgClarity += $req->getAiClarityScore();
                $avgBudget += $req->getAiBudgetRealismScore();
                $avgTimeline += $req->getAiTimelineFeasibilityScore();
                $requestsWithAI++;
            }
            if (!empty($req->getAiFlags())) {
                $flaggedRequests[] = $req;
            }
        }

        if ($requestsWithAI > 0) {
            $avgClarity /= $requestsWithAI;
            $avgBudget /= $requestsWithAI;
            $avgTimeline /= $requestsWithAI;
        }

        return $this->render('back/collab/ai_oversight_hub.html.twig', [
            'requests' => $allRequests,
            'flagged_requests' => $flaggedRequests,
            'stats' => [
                'total_ai_usage' => $totalUsage,
                'avg_clarity' => (int) $avgClarity,
                'avg_budget' => (int) $avgBudget,
                'avg_timeline' => (int) $avgTimeline,
                'requests_analyzed' => $requestsWithAI
            ]
        ]);
    }
}
