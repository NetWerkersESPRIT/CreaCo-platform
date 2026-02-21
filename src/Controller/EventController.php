<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\MeetingLinkGenerator;
use App\Service\ImgbbService;
use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\Notification;

final class EventController extends AbstractController
{


    #[Route('/event', name: 'event_list')]
    public function list(Request $request, EventRepository $repo): Response
    {
        if (!in_array($request->getSession()->get('user_role'), ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_CONTENT_CREATOR'])) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('event/index.html.twig', [
            'events' => $repo->findAll(),
        ]);
    }


    #[Route('/event/new', name: 'event_new')]
    public function new(Request $request, EntityManagerInterface $em, MeetingLinkGenerator $generator, ImgbbService $imgbbService): Response
    {
        if (!in_array($request->getSession()->get('user_role'), ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_CONTENT_CREATOR'])) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('app_auth');
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imageUrl = $imgbbService->upload($imageFile);
                if ($imageUrl) {
                    $event->setImagePath($imageUrl);
                }
            }
            if ($event->getType() === 'online') {
                $event->setMeetingLink($generator->generateJitsiLink());
                $event->setPlatform('Jitsi Meet');
            }
            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/event/{id}/delete', name: 'event_delete')]
    public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        if (!in_array($request->getSession()->get('user_role'), ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_CONTENT_CREATOR'])) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('app_auth');
        }
        $em->remove($event);
        $em->flush();

        return $this->redirectToRoute('event_list');
    }

    #[Route('/event/{id}/edit', name: 'event_edit')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em, ImgbbService $imgbbService): Response
    {

        if (!in_array($request->getSession()->get('user_role'), ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_CONTENT_CREATOR'])) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imageUrl = $imgbbService->upload($imageFile);
                if ($imageUrl) {
                    $event->setImagePath($imageUrl);
                }
            }
            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/event/{id}', name: 'event_show', requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/member/events', name: 'member_event_list')]
    public function memberIndex(Request $request, EventRepository $repo): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_MEMBER') {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('event/member_index.html.twig', [
            'events' => $repo->findAll(),
        ]);
    }

    #[Route('/member/my-reservations', name: 'member_reservations')]
    public function myReservations(Request $request, EntityManagerInterface $em, \App\Repository\UsersRepository $usersRepo): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $user = $usersRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        $reservations = $em->getRepository(\App\Entity\Reservation::class)->findBy(['user' => $user]);

        return $this->render('event/my_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }



    #[Route('/event/{id}/reserve', name: 'event_reserve', requirements: ['id' => '\d+'])]
    public function reserve(Event $event, EntityManagerInterface $em, Request $request, \App\Repository\UsersRepository $usersRepo): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $user = $usersRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        // Check if already reserved
        foreach ($event->getReservations() as $reservation) {
            if ($reservation->getUser() === $user) {
                $this->addFlash('warning', 'You have already reserved this event.');
                return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
            }
        }

        $reservation = new \App\Entity\Reservation();
        $reservation->setEvent($event);
        $reservation->setUser($user);
        $reservation->setReservedAt(new \DateTimeImmutable());
        $reservation->setStatus('pending');

        $em->persist($reservation);

        // Notificationsssssssssssss
        $managers = $usersRepo->findBy(['role' => ['ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_CONTENT_CREATOR']]);
        foreach ($managers as $manager) {
            $notification = new Notification();
            $notification->setMessage("New reservation request for event: " . $event->getName() . " by " . $user->getUsername());
            $notification->setUserId($manager); // Looking at Notification entity,the field is user_id
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());
            $em->persist($notification);
        }

        $em->flush();

        $this->addFlash('success', 'Reservation requested successfully!');
        return $this->redirectToRoute('member_reservations');
    }

    #[Route('/event/{id}/manage', name: 'event_manage', requirements: ['id' => '\d+'])]
    public function manage(Event $event): Response
    {
        return $this->render('event/manage.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/reservation/{id}/{status}', name: 'reservation_update', requirements: ['status' => 'validated|cancelled'])]
    public function updateReservation(\App\Entity\Reservation $reservation, string $status, EntityManagerInterface $em): Response
    {
        $reservation->setStatus($status);

        // Notificationsssssssssssss
        $member = $reservation->getUser();
        if ($member) {
            $notification = new Notification();
            $statusText = ($status === 'validated') ? 'confirmed' : 'cancelled';
            $notification->setMessage("Your reservation for " . $reservation->getEvent()->getName() . " has been " . $statusText . ".");
            $notification->setUserId($member);
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());
            $em->persist($notification);
        }

        $em->flush();

        $this->addFlash('success', 'Reservation status updated to ' . $status);

        return $this->redirectToRoute('event_manage', ['id' => $reservation->getEvent()->getId()]);
    }
}