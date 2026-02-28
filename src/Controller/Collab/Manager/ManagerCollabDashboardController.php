<?php

namespace App\Controller\Collab\Manager;

use App\Repository\ContractRepository;
use App\Repository\CollabRequestRepository;
use App\Repository\CollaboratorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/manager/collaboration-hub')]
class ManagerCollabDashboardController extends AbstractController
{
    #[Route('/', name: 'app_manager_collab_dashboard', methods: ['GET'])]
    public function index(
        Request $request,
        ContractRepository $contractRepo,
        CollabRequestRepository $requestRepo,
        CollaboratorRepository $collabRepo
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $userRole = $session->get('user_role');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            return $this->redirectToRoute('app_auth');
        }

        // Stats for the dashboard
        $stats = [
            'total_partners' => $collabRepo->count([]), // Total visibility of ecosystem
            'managed_contracts' => count($contractRepo->findForRevisor($userId)),
            'active_contracts' => $contractRepo->countActiveForRevisor($userId),
            'total_budget' => $contractRepo->getTotalBudgetForRevisor($userId),
            'pending_requests' => $requestRepo->countPendingByRevisor($userId),
        ];

        // "Strategic Movements" - Recent activities
        $recentContracts = $contractRepo->filterContracts($userId, $userRole, null, null);
        $recentContracts = array_slice($recentContracts, 0, 5);

        return $this->render('manager/collab_dashboard/index.html.twig', [
            'stats' => $stats,
            'recent_contracts' => $recentContracts,
        ]);
    }
}
