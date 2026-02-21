<?php

namespace App\Controller;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleOAuthController extends AbstractController
{
    #[Route('/google/oauth/login', name: 'google_oauth_login')]
    public function login(): RedirectResponse
    {
        $client = $this->makeClient();
        return new RedirectResponse($client->createAuthUrl());
    }

    #[Route('/google/oauth/callback', name: 'google_oauth_callback')]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Missing code', 400);
        }

        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return new Response('OAuth error: ' . ($token['error_description'] ?? $token['error']), 400);
        }

        $tokenPath = $this->getParameter('kernel.project_dir') . '/config/google/token.json';
        file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT));

        return new Response('✅ Connected to Google Drive! You can close this page.');
    }

    private function makeClient(): GoogleClient
    {
        $projectDir = $this->getParameter('kernel.project_dir');

        $client = new GoogleClient();
        $client->setAuthConfig($projectDir . '/config/google/credentials.json');
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setRedirectUri('http://127.0.0.1:8000/google/oauth/callback');

        return $client;
    }
}
