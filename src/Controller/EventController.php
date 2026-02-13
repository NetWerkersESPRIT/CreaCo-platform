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

final class EventController extends AbstractController
{


    #[Route('/event', name: 'event_list')]
    public function list(EventRepository $repo): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $repo->findAll(),
        ]);
    }


    #[Route('/event/new', name: 'event_new')]
    public function new(Request $request, EntityManagerInterface $em, MeetingLinkGenerator $generator, ImgbbService $imgbbService): Response
    {
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
    public function delete(Event $event, EntityManagerInterface $em): Response
    {
        $em->remove($event);
        $em->flush();

        return $this->redirectToRoute('event_list');
    }

    #[Route('/event/{id}/edit', name: 'event_edit')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em, ImgbbService $imgbbService): Response
    {
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

    #[Route('/event/{id}/reserve', name: 'event_reserve', requirements: ['id' => '\d+'])]
    public function reserve(Event $event, EntityManagerInterface $em): Response
    {
        // Assuming user is logged in
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login'); // Or handle error
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
        $em->flush();

        $this->addFlash('success', 'Reservation requested successfully!');
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
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
        $em->flush();

        $this->addFlash('success', 'Reservation status updated to ' . $status);

        return $this->redirectToRoute('event_manage', ['id' => $reservation->getEvent()->getId()]);
    }
}
