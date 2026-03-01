<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use App\Repository\TaskRepository;
use App\Repository\MissionRepository;
use App\Repository\IdeaRepository;
use App\Repository\CoursRepository;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdminAiAssistantController extends AbstractController
{
    private string $groqApiKey;
    private HttpClientInterface $httpClient;

    public function __construct(string $groqApiKey, HttpClientInterface $httpClient)
    {
        $this->groqApiKey = $groqApiKey;
        $this->httpClient = $httpClient;
    }

    #[Route('/admin/ai/chat', name: 'admin_ai_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        UsersRepository $userRepo,
        TaskRepository $taskRepo,
        MissionRepository $missionRepo,
        IdeaRepository $ideaRepo,
        CoursRepository $coursRepo,
        PostRepository $postRepo,
        CommentRepository $commentRepo
    ): JsonResponse {
        // Security check
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message is empty'], 400);
        }

        // Fetch stats
        $stats = [
            'total_users' => $userRepo->count([]),
            'total_tasks' => $taskRepo->count([]),
            'total_missions' => $missionRepo->count([]),
            'total_ideas' => $ideaRepo->count([]),
            'total_courses' => $coursRepo->count([]),
            'forum_posts' => $postRepo->count([]),
            'forum_comments' => $commentRepo->count([]),
        ];

        $statsText = sprintf(
            "Current Application Statistics:\n" .
            "- Total Users: %d\n" .
            "- Total Tasks: %d\n" .
            "- Total Missions: %d\n" .
            "- Total Ideas: %d\n" .
            "- Total Courses: %d\n" .
            "- Forum Activity: %d posts and %d comments\n",
            $stats['total_users'],
            $stats['total_tasks'],
            $stats['total_missions'],
            $stats['total_ideas'],
            $stats['total_courses'],
            $stats['forum_posts'],
            $stats['forum_comments']
        );

        $systemPrompt = "You are an admin assistant for a Symfony web application. " .
            "Your goal is to help the admin manage the platform and provide information about its status. " .
            "Here are the current stats of the application:\n" . $statsText .
            "\nBe professional, helpful, and concise.";

        if (!$this->groqApiKey) {
            return new JsonResponse(['error' => 'AI API key not configured'], 500);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            // Try to catch error details if not 200
            if ($response->getStatusCode() !== 200) {
                $errorBody = $response->getContent(false);
                return new JsonResponse(['error' => 'AI API Error (' . $response->getStatusCode() . '): ' . $errorBody], 500);
            }

            $result = $response->toArray();
            return new JsonResponse([
                'reply' => $result['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to communicate with AI: ' . $e->getMessage()], 500);
        }
    }
}
