<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\VerifyCodeType;


final class ForgetpasswordController extends AbstractController

{
    #[Route('/forgetpassword', name: 'app_forgetpassword', methods: ['GET', 'POST'])]
    public function index(): Response
    {

        return $this->render('forgetpassword/index.html.twig', [
            'controller_name' => 'ForgetpasswordController',
        ]);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        CacheInterface $cache
    ): Response {
        $email = $request->request->get('email');

        $user = $em->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Email not found');
            return $this->redirectToRoute('app_forgetpassword');
        }

        $code = random_int(100000, 999999);

        $cacheKey = 'reset_code_' . hash('sha256', $email);

        $storedCode = $cache->get($cacheKey, function (ItemInterface $item) use ($code) {
            $item->expiresAfter(900);
            return $code;
        });

        $emailMessage = (new Email())
            ->from('anas.sgh222@gmail.com')
            ->to($user->getEmail())
            ->subject('Password Reset Code')
            ->text('Your reset code is: ' . $storedCode);

        $mailer->send($emailMessage);

        return $this->redirectToRoute('verify_code_form', ['email' => $email]);
    }

    #[Route('/verify_code', name: 'verify_code_form')]
    public function verifyCodeForm(Request $request): Response
    {
        $email = $request->get('email');

        return $this->render('forgetpassword/code.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/code_check', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(Request $request, CacheInterface $cache): Response
    {
        $code = $request->request->get('code');
        $email = $request->request->get('email');
        $cacheKey = 'reset_code_' . hash('sha256', $email);

        $storedCode = $cache->get($cacheKey, fn() => null);

        if (!$storedCode) {
            $this->addFlash('error', 'Expired code');
            return $this->redirectToRoute('verify_code_form', ['email' => $email]);
        }

        if ((string)$storedCode !== $code) {
            $this->addFlash('error', 'Invalid code');
            return $this->redirectToRoute('verify_code_form', ['email' => $email]);
        }

        $cache->delete($cacheKey);
        return $this->redirectToRoute('app_reset_password', ['email' => $email]);
    }

    #[Route('/reset_password', name: 'app_reset_password')]
    public function resetPassword(Request $request, EntityManagerInterface $em,): Response
    {
        $email = $request->get('email');
        $user = $em->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('app_forgetpassword');
        }

        if ($request->isMethod('POST')) {

            $newPassword = $request->request->get('password');
            $newPassword1 = $request->request->get('password1');

            if (!$newPassword || trim($newPassword) === '') {
                $this->addFlash('error', 'Please enter a password');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            // 2️⃣ Minimum length 6
            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Your password must be at least 6 characters long');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            // 4️⃣ Regex: at least one uppercase letter and one number
            if (!preg_match('/^(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
                $this->addFlash('error', 'Your password must contain at least one uppercase letter and one number');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            if ($newPassword !== $newPassword1) {
                $this->addFlash('error', 'Passwords do not match');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $user->setPassword($newPassword);
            $em->flush();

            return $this->redirectToRoute('app_auth');
        }

        return $this->render('forgetpassword/reset_password.html.twig', [
            'email' => $email,
        ]);
    }
}
