<?php

namespace App\Controller;

use App\Entity\HelpTicket;
use App\Entity\Notification;
use App\Form\HelpTicketResponseType;
use App\Repository\HelpTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/help-requests')]
class AdminHelpTicketController extends AbstractController
{
    #[Route('', name: 'app_admin_help_ticket_index', methods: ['GET'])]
    public function index(Request $request, HelpTicketRepository $helpTicketRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $tickets = $helpTicketRepository->findBy([], ['status' => 'ASC', 'priority' => 'DESC', 'createdAt' => 'DESC']);

        return $this->render('admin/help_ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_help_ticket_show', methods: ['GET', 'POST'])]
    public function show(Request $request, HelpTicket $helpTicket, EntityManagerInterface $em): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(HelpTicketResponseType::class, $helpTicket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $helpTicket->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            if ($helpTicket->getAdminResponse()) {
                $notification = new Notification();
                $notification->setMessage(sprintf('Admin replied to your support request "%s".', $helpTicket->getSubject()));
                $notification->setIsRead(false);
                $notification->setCreatedAt(new \DateTime());
                $notification->setUserId($helpTicket->getCreator());
                $notification->setTargetUrl($this->generateUrl('app_help_ticket_show', ['id' => $helpTicket->getId()]));
                $notification->setType('support');
                $notification->setRelatedId($helpTicket->getId());
                $notification->setStatus($helpTicket->getStatus());
                $em->persist($notification);
                $em->flush();
            }

            $this->addFlash('success', 'Support request updated and response sent to the creator.');
            return $this->redirectToRoute('app_admin_help_ticket_show', ['id' => $helpTicket->getId()]);
        }

        return $this->render('admin/help_ticket/show.html.twig', [
            'ticket' => $helpTicket,
            'form' => $form->createView(),
        ]);
    }
}
