<?php

namespace App\Controller;

use App\Entity\HelpTicket;
use App\Entity\Users;
use App\Form\HelpTicketResponseType;
use App\Repository\HelpTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/help-requests')]
class HelpTicketController extends AbstractController
{
    #[Route('', name: 'app_help_ticket_index', methods: ['GET'])]
    public function index(Request $request, HelpTicketRepository $helpTicketRepository, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        $tickets = $helpTicketRepository->findByCreator($user);

        return $this->render('help_ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}', name: 'app_help_ticket_show', methods: ['GET'])]
    public function show(Request $request, HelpTicket $helpTicket): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId || $helpTicket->getCreator()->getId() !== $userId) {
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('help_ticket/show.html.twig', [
            'ticket' => $helpTicket,
        ]);
    }
}
