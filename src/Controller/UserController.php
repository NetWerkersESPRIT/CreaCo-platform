<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UserType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user/new', name: 'app_useradd')]
    public function createuser(Request $request, EntityManagerInterface $em): Response
    {
        $user = new Users();
        
        $form = $this->createForm(UserType::class, $user);
        $user->setRole('ROLE_CONTENT_CREATOR');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($user);
            $em->flush();
            
            $user->setGroupId($user->getId());
            $em->flush();
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, UsersRepository $userRepository): Response
    {
        if (!$request->getSession()) {
            $this->addFlash('warning', 'Access restricted.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('user/profile.html.twig');
    }
}
