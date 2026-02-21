<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/content')]
class ContentController extends AbstractController
{
    #[Route('/ressource/{id}', name: 'app_content_ressource_view', methods: ['GET'])]
    public function viewRessource(Request $request, Ressource $ressource): Response
    {
        // Vérifier si l'utilisateur a le rôle ROLE_CONTENT_CREATOR
        if ($request->getSession()->get('user_role') !== 'ROLE_CONTENT_CREATOR') {
            throw $this->createAccessDeniedException('Access denied. Content Creator role required.');
        }

        // Rediriger vers la page d'affichage appropriée selon le type
        return match($ressource->getType()) {
            'PDF' => $this->render('content/ressource/pdf.html.twig', ['ressource' => $ressource]),
            'IMAGE' => $this->render('content/ressource/image.html.twig', ['ressource' => $ressource]),
            'VIDEO' => $this->render('content/ressource/video.html.twig', ['ressource' => $ressource]),
            'FILE' => $this->render('content/ressource/file.html.twig', ['ressource' => $ressource]),
            default => $this->render('content/ressource/default.html.twig', ['ressource' => $ressource]),
        };
    }
}
