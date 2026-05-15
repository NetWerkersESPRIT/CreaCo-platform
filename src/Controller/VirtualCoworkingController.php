<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VirtualCoworkingController extends AbstractController
{
    #[Route('/coworking-space', name: 'app_coworking_space')]
    public function index(Request $request): Response
    {
        // 1. Authentication Check: Ensure only authenticated creators/managers can access the space
        if (!$request->getSession()->get('user_id')) {
            $this->addFlash('error', 'Please log in to enter the Virtual Coworking Space.');
            return $this->redirectToRoute('app_auth');
        }

        /**
         * WorkAdventure Integration Logic:
         * We embed the WorkAdventure room URL in an iframe. 
         * WorkAdventure handles the multiplayer movement, spatial audio, and character interaction.
         * The CreaCo platform provides the immersive shell, loading transitions, and UI consistency.
         */
        $workAdventureUrl = 'https://play.workadventu.re/@/esprit-1778824285/creaco/modern-hackathon';
        
        return $this->render('coworking/index.html.twig', [
            'work_adventure_url' => $workAdventureUrl,
        ]);
    }
}
