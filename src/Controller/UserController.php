<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\ProfileType;
use App\Form\UserType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;


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
        $session = $request->getSession();
        $userId = $session ? $session->get('user_id') : null;

        if (!$userId) {
            $this->addFlash('warning', 'Access restricted.');
            return $this->redirectToRoute('app_auth');
        }

        $currentUser = $userRepository->find($userId);
        if (!$currentUser) {
            return $this->redirectToRoute('app_auth');
        }

        $groupMembers = $userRepository->createQueryBuilder('u')
            ->where('u.groupid = :groupid')
            ->andWhere('u.id != :id')
            ->setParameter('groupid', $currentUser->getGroupid())
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();

        return $this->render('user/profile.html.twig', [
            'group_members' => $groupMembers
        ]);
    }

    #[Route('/signup/google', name: 'google_signup_start')]
    public function googleSignupStart(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google_signup')
            ->redirect(
                ['email', 'profile'],
                ['state' => 'signup']
            );
    }

    #[Route('/signup/google/check', name: 'google_signup_check')]
    public function googleSignupCheck() {}

    #[Route('/add-member', name: 'app_add_member', methods: ['GET', 'POST'])]
    public function addMember(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $currentUser = $em->getRepository(Users::class)->find($userId);
        if (!$currentUser) {
            return $this->redirectToRoute('app_auth');
        }

        $user = new Users();
        $form = $this->createForm(UserType::class, $user, [
            'include_role' => true,
            'optional_numtel' => true
        ]);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setGroupid($currentUser->getGroupid());

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Member added successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('user/add_member.html.twig', [
            'form' => $form->createView(),
            'app_user' => $currentUser
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $user->setPassword($newPassword);
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
            'app_user' => $user
        ]);
    }
}
