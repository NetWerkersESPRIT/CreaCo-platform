<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;

class FaceIdController extends AbstractController
{
    /**
     * Endpoint to register Face ID for the currently logged-in user.
     */
    #[Route('/face-id/register', name: 'app_face_id_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var Users|null $user */
        $user = $this->getUser();
        
        if (!$user instanceof Users) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to register Face ID.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!$descriptor || !is_array($descriptor)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid or missing facial descriptor data.'], 400);
        }

        // Save the descriptor as a JSON array in the database
        $user->setFaceDescriptor($descriptor);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Face ID has been registered successfully!']);
    }

    /**
     * Endpoint to login using Face ID by comparing the scanned descriptor with all stored descriptors.
     */
    #[Route('/face-id/login', name: 'app_face_id_login', methods: ['POST'])]
    public function login(
        Request $request, 
        EntityManagerInterface $em, 
        UserAuthenticatorInterface $authenticator,
        LoginFormAuthenticator $formAuthenticator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $inputDescriptor = $data['descriptor'] ?? null;
        $email = $data['email'] ?? null;

        if (!$inputDescriptor || !is_array($inputDescriptor)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid facial data received.'], 400);
        }

        // Fetch users to compare against
        $userRepo = $em->getRepository(Users::class);
        if ($email) {
            $user = $userRepo->findOneBy(['email' => $email]);
            $users = $user ? [$user] : [];
        } else {
            $users = $userRepo->createQueryBuilder('u')
                ->where('u.faceDescriptor IS NOT NULL')
                ->getQuery()
                ->getResult();
        }

        $bestMatch = null;
        $minDistance = 0.55; // Threshold for face-api.js (lower means stricter)

        foreach ($users as $user) {
            $savedDescriptor = $user->getFaceDescriptor();
            
            // Basic safety check for descriptor format
            if (!is_array($savedDescriptor)) continue;

            $distance = $this->euclideanDistance($inputDescriptor, $savedDescriptor);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatch = $user;
            }
        }

        if ($bestMatch) {
            // Programmatically authenticate the user using the existing LoginFormAuthenticator logic
            $authenticator->authenticateUser(
                $bestMatch,
                $formAuthenticator,
                $request
            );

            // Synchronize session variables used by the legacy/custom parts of the app
            $request->getSession()->set('user_id', $bestMatch->getId());
            $request->getSession()->set('user_role', $bestMatch->getRole());
            $request->getSession()->set('username', $bestMatch->getUsername());

            return new JsonResponse([
                'success' => true, 
                'message' => 'Face recognized! Redirecting...',
                'redirect' => $bestMatch->getRole() === 'ROLE_ADMIN' ? $this->generateUrl('app_admin_dashboard') : $this->generateUrl('app_home')
            ]);
        }

        return new JsonResponse([
            'success' => false, 
            'message' => 'Face not recognized. Please ensure you are well-lit and facing the camera directly.'
        ], 401);
    }

    /**
     * Calculates the Euclidean distance between two facial descriptors.
     */
    private function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) return 1.0;
        
        $sum = 0;
        foreach ($a as $i => $val) {
            $sum += pow($val - $b[$i], 2);
        }
        return sqrt($sum);
    }
}
