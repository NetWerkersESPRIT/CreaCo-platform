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
use App\Entity\Group;
use App\Entity\Notification;
use App\Repository\GroupRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;


final class UserController extends AbstractController
{
    #[Route('/user/new', name: 'app_useradd')]
    public function createuser(Request $request, EntityManagerInterface $em, UsersRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new Users();

        $form = $this->createForm(UserType::class, $user);
        $user->setRole('ROLE_CONTENT_CREATOR');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            // 🎉 Welcome notification for the new user
            $welcomeNotif = new Notification();
            $welcomeNotif->setUserId($user);
            $welcomeNotif->setMessage('Welcome to CreaCo, ' . $user->getUsername() . '! 🎉 Your account has been created successfully.');
            $welcomeNotif->setType('welcome');
            $welcomeNotif->setIsRead(false);
            $welcomeNotif->setStatus('unread');
            $welcomeNotif->setCreatedAt(new \DateTime());
            $welcomeNotif->setTargetUrl('/profile');
            $em->persist($welcomeNotif);

            // 🔔 Notify all Admins of the new registration
            $admins = $userRepository->findBy(['role' => 'ROLE_ADMIN']);
            foreach ($admins as $admin) {
                $adminNotif = new Notification();
                $adminNotif->setUserId($admin);
                $adminNotif->setMessage('🆕 New user registered: ' . $user->getUsername() . ' (' . $user->getEmail() . ')');
                $adminNotif->setType('system');
                $adminNotif->setIsRead(false);
                $adminNotif->setStatus('unread');
                $adminNotif->setCreatedAt(new \DateTime());
                $adminNotif->setTargetUrl('/admin');
                $em->persist($adminNotif);
            }

            $em->flush();
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, UsersRepository $userRepository, EntityManagerInterface $em): Response
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

        $groupMembers = [];
        
        // 1. Get members and owners of any group this user has JOINED
        foreach ($currentUser->getGroups() as $group) {
            // Add other members of the same group
            foreach ($group->getMembers() as $member) {
                if ($member->getId() !== $currentUser->getId()) {
                    $groupMembers[$member->getId()] = $member;
                }
            }
            // Also add the owner of this group as a colleague
            $owner = $group->getOwner();
            if ($owner && $owner->getId() !== $currentUser->getId()) {
                $groupMembers[$owner->getId()] = $owner;
            }
        }

        // 2. If the user OWNS any group (Content Creator), get all its members
        $ownedGroups = $em->getRepository(Group::class)->findBy(['owner' => $currentUser]);
        foreach ($ownedGroups as $group) {
            foreach ($group->getMembers() as $member) {
                if ($member->getId() !== $currentUser->getId()) {
                    $groupMembers[$member->getId()] = $member;
                }
            }
        }

        return $this->render('user/profile.html.twig', [
            'group_members' => array_values($groupMembers),
            'app_user' => $currentUser
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
    public function addMember(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $currentUser = $em->getRepository(Users::class)->find($userId);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_CONTENT_CREATOR') {
             // Only creators can add members to their group
            $this->addFlash('error', 'Only content creators can add members.');
            return $this->redirectToRoute('app_profile');
        }

        // Get or Create the Group for this creator
        $groupRepo = $em->getRepository(Group::class);
        $group = $groupRepo->findOneBy(['owner' => $currentUser]);

        if (!$group) {
            $group = new Group();
            $group->setName($currentUser->getUsername() . "'s Group");
            $group->setOwner($currentUser);
            $em->persist($group);
            $em->flush();
            
            $em->persist($group);
            $em->flush();
        }

        $user = new Users();
        $form = $this->createForm(UserType::class, $user, [
            'include_role' => true,
            'optional_numtel' => true
        ]);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Check if user already exists
            $existingUser = $em->getRepository(Users::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'A user with this email already exists. Use "Add Existing Member" instead.');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($hashedPassword);
                
                $group->addMember($user);
                $em->persist($user);
                $em->flush();

                // Notify Admins
                $admins = $em->getRepository(Users::class)->findBy(['role' => 'ROLE_ADMIN']);
                foreach ($admins as $admin) {
                    $notif = new Notification();
                    $notif->setUserId($admin);
                    $notif->setMessage("New user created: " . $user->getUsername());
                    $notif->setType('system');
                    $notif->setIsRead(false);
                    $notif->setCreatedAt(new \DateTime());
                    $em->persist($notif);
                }
                $em->flush();

                $this->addFlash('success', 'Member created and added successfully!');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('user/add_member.html.twig', [
            'form' => $form->createView(),
            'app_user' => $currentUser,
            'group' => $group
        ]);
    }

    #[Route('/member-search', name: 'app_member_search', methods: ['GET'])]
    public function searchMembers(Request $request, UsersRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $currentUser = $em->getRepository(Users::class)->find($userId);

        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $group = $em->getRepository(Group::class)->findOneBy(['owner' => $currentUser]);
        $existingMemberIds = [0]; // Default to prevent empty IN clause issues if needed, or handle null

        if ($group) {
            foreach ($group->getMembers() as $member) {
                $existingMemberIds[] = $member->getId();
            }
        }

        $query = $request->query->get('q', '');
        
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', ['ROLE_MEMBER', 'ROLE_MANAGER']);

        if (!empty($query)) {
            $qb->andWhere('(u.email LIKE :query OR u.username LIKE :query)')
               ->setParameter('query', '%' . $query . '%');
        }

        $members = $qb->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($members as $member) {
            $isAlreadyMember = false;
            if ($group) {
                $isAlreadyMember = $group->getMembers()->contains($member);
            }

            $results[] = [
                'id' => $member->getId(),
                'email' => $member->getEmail(),
                'username' => $member->getUsername(),
                'role' => $member->getRole(),
                'isAlreadyMember' => $isAlreadyMember,
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/attach-member/{id}', name: 'app_attach_member', methods: ['POST'])]
    public function attachMember(Users $member, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $currentUser = $em->getRepository(Users::class)->find($userId);

        if (!$currentUser || $currentUser->getRole() !== 'ROLE_CONTENT_CREATOR') {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $group = $em->getRepository(Group::class)->findOneBy(['owner' => $currentUser]);
        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        if (!in_array($member->getRole(), ['ROLE_MEMBER', 'ROLE_MANAGER'])) {
            return new JsonResponse(['error' => 'User is not a member or manager'], 400);
        }

        if ($group->getMembers()->contains($member)) {
            return new JsonResponse(['error' => 'Member already in group'], 400);
        }

        // Check if an invitation is already pending
        $existingInvitation = $em->getRepository(Notification::class)->findOneBy([
            'user_id' => $member,
            'type' => 'invitation',
            'relatedId' => $group->getId(),
            'status' => 'pending'
        ]);

        if ($existingInvitation) {
            return new JsonResponse(['error' => 'Invitation already sent'], 400);
        }

        $notification = new Notification();
        $notification->setUserId($member);
        $notification->setMessage($currentUser->getUsername() . " invited you to join their group: " . $group->getName());
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTime());
        $notification->setType('invitation');
        $notification->setRelatedId($group->getId());
        $notification->setStatus('pending');

        $em->persist($notification);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Invitation sent successfully.']);
    }

    #[Route('/invitation/accept/{id}', name: 'app_invitation_accept', methods: ['POST'])]
    public function acceptInvitation(Notification $notification, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $currentUserId = $session->get('user_id');

        if (!$currentUserId || $notification->getUserId()->getId() !== (int)$currentUserId) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        if ($notification->getType() !== 'invitation' || $notification->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'Invalid invitation'], 400);
        }

        $group = $em->getRepository(Group::class)->find($notification->getRelatedId());
        if (!$group) {
            return new JsonResponse(['error' => 'Group no longer exists'], 404);
        }

        $user = $notification->getUserId();
        
        // Add user to group
        $group->addMember($user);
        
        // Update notification - or in this case, delete it as requested
        $em->remove($notification);
        
        // Populate legacy groupid
        if (!$user->getGroupid()) {
            $user->setGroupid($group->getId());
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/invitation/deny/{id}', name: 'app_invitation_deny', methods: ['POST'])]
    public function denyInvitation(Notification $notification, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $currentUserId = $session->get('user_id');

        if (!$currentUserId || $notification->getUserId()->getId() !== (int)$currentUserId) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        if ($notification->getType() !== 'invitation' || $notification->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'Invalid invitation'], 400);
        }

        $em->remove($notification);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/detach-member/{id}', name: 'app_member_detach', methods: ['POST'])]
    public function detachMember(Users $member, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $currentUser = $em->getRepository(Users::class)->find($userId);

        if (!$currentUser || $currentUser->getRole() !== 'ROLE_CONTENT_CREATOR') {
            $this->addFlash('error', 'Unauthorized');
            return $this->redirectToRoute('app_profile');
        }

        if (!$this->isCsrfTokenValid('detach' . $member->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_profile');
        }

        $group = $em->getRepository(Group::class)->findOneBy(['owner' => $currentUser]);
        if (!$group) {
            $this->addFlash('error', 'Group not found');
            return $this->redirectToRoute('app_profile');
        }

        if ($group->getMembers()->contains($member)) {
            $group->removeMember($member);
            $em->flush();
            $this->addFlash('success', 'Member removed from group successfully.');
        } else {
            $this->addFlash('error', 'Member is not in your group.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
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
            $currentPasswordInput = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if ($newPassword) {
                if (!$currentPasswordInput || !$passwordHasher->isPasswordValid($user, $currentPasswordInput)) {
                    $this->addFlash('error', 'You must provide your correct current password to set a new one.');
                    return $this->render('user/edit_profile.html.twig', [
                        'form' => $form->createView(),
                        'app_user' => $user
                    ]);
                }
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
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
