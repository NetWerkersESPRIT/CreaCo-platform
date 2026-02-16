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

final class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(Request $request, \App\Repository\CoursRepository $coursRepo): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        // Get Top 5 Viewed Courses
        $topCourses = $coursRepo->findBy([], ['views' => 'DESC'], 5);
        
        // Prepare data for Chart.js
        $courseTitles = [];
        $courseViews = [];
        foreach ($topCourses as $c) {
            $courseTitles[] = $c->getTitre();
            $courseViews[] = $c->getViews() ?? 0;
        }

        return $this->render('admin/dashboard.html.twig', [
            'courseTitles' => json_encode($courseTitles),
            'courseViews' => json_encode($courseViews),
            'topCourses' => $topCourses
        ]);
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request, UsersRepository $userRepository, \App\Repository\PostRepository $postRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        $pendingCount = $postRepository->countPending();

        $users = $userRepository->createQueryBuilder('u')
            ->where('u.role != :role')
            ->setParameter('role', 'ROLE_ADMIN')
            ->getQuery()
            ->getResult();

        return $this->render('admin/admin.html.twig', [
            'users' => $users,
            'pendingCount' => $pendingCount,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_user_edit')]
    public function edit(
        Users $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $oldPassword = $user->getPassword();
        $form = $this->createForm(UserType::class, $user, [
            'optional_password' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if (empty($newPassword)) {
                $user->setPassword($oldPassword);
            }
            $em->flush();

            return $this->redirectToRoute('app_admin');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/delete/{id}', name: 'user_delete')]
    public function delete(Request $request, int $id, EntityManagerInterface $em, UsersRepository $repo): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }
        $user = $repo->find($id);

        if ($user) {
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin');
    }
}
