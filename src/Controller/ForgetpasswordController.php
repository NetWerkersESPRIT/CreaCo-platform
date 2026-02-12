<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ForgetpasswordController extends AbstractController
{
    #[Route('/forgetpassword', name: 'app_forgetpassword', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        UsersRepository $usersRepository, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (!$email || !$password || !$confirmPassword) {
                $this->addFlash('warning', 'Please fill in all fields.');
                return $this->redirectToRoute('app_forgetpassword');
            }

            if ($password !== $confirmPassword) {
                $this->addFlash('warning', 'Passwords do not match.');
                return $this->redirectToRoute('app_forgetpassword');
            }

            $user = $usersRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('warning', 'No user found with this email address.');
                return $this->redirectToRoute('app_forgetpassword');
            }

            $user->setPassword($password);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully! You can now log in with your new password.');
            
            return $this->redirectToRoute('app_auth'); 
        }

        return $this->render('forgetpassword/index.html.twig', [
            'controller_name' => 'ForgetpasswordController',
        ]);
    }
}
