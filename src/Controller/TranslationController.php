<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api')]
class TranslationController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    #[Route('/translate', name: 'api_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'Please login to use translation'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $targetLang = $data['target_lang'] ?? 'en';

        if (empty($text)) {
            return new JsonResponse(['error' => 'No text provided'], 400);
        }

        $chunks = $this->splitTextIntoChunks($text, 450);
        $translatedParts = [];

        foreach ($chunks as $chunk) {
            $translated = $this->translateChunk($chunk, $targetLang);
            if ($translated === null) {
                return new JsonResponse(['error' => 'Translation service unavailable'], 500);
            }
            $translatedParts[] = $translated;
        }

        return new JsonResponse([
            'success' => true,
            'translated_text' => implode(' ', $translatedParts),
            'target_lang' => $targetLang,
        ]);
    }

    private function splitTextIntoChunks(string $text, int $maxLength): array
    {
        $words = preg_split('/\s+/', $text);
        $chunks = [];
        $currentChunk = '';

        foreach ($words as $word) {
            if (strlen($currentChunk . ' ' . $word) <= $maxLength) {
                $currentChunk .= ($currentChunk ? ' ' : '') . $word;
            } else {
                if ($currentChunk) {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $word;
            }
        }

        if ($currentChunk) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    private function translateChunk(string $text, string $targetLang): ?string
    {
        $langPair = 'en|' . $targetLang;
        
        if ($targetLang === 'fr') {
            $langPair = 'en|fr';
        } elseif ($targetLang === 'en') {
            $langPair = 'fr|en';
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q' => $text,
                    'langpair' => $langPair,
                ],
                'timeout' => 15,
            ]);

            $result = $response->toArray();
            $translated = $result['responseData']['translatedText'] ?? null;

            if ($translated && !in_array($translated, ['INVALID LANGUAGE PAIR', 'NO QUERY SPECIFIED', 'PLEASE SELECT TWO DISTINCT LANGUAGES'])) {
                return $translated;
            }
            
            $reversePair = ($targetLang === 'fr') ? 'fr|en' : 'en|fr';
            $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q' => $text,
                    'langpair' => $reversePair,
                ],
                'timeout' => 15,
            ]);
            
            $result = $response->toArray();
            $translated = $result['responseData']['translatedText'] ?? null;
            
            if ($translated && !in_array($translated, ['INVALID LANGUAGE PAIR', 'NO QUERY SPECIFIED', 'PLEASE SELECT TWO DISTINCT LANGUAGES'])) {
                return $translated;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    #[Route('/languages', name: 'api_languages', methods: ['GET'])]
    public function getLanguages(): JsonResponse
    {
        return new JsonResponse([
            'languages' => [
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'fr', 'name' => 'French'],
            ]
        ]);
    }
}
