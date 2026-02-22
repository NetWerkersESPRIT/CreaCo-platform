<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TextGearsService
{
    private const DEFAULT_LANGUAGE = 'en-US';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
    ) {}

    /**
     * Returns corrected text using /correct
     * Defaults to en-US.
     */
    public function correct(string $text): string
    {
        $original = trim($text);
        if ($original === '') {
            return $original;
        }

        try {
            $resp = $this->http->request('GET', 'https://api.textgears.com/correct', [
                'query' => [
                    'text' => $original,
                    'language' => self::DEFAULT_LANGUAGE,
                    'key' => $this->apiKey,
                ],
                'timeout' => 8,
            ]);

            $data = $resp->toArray(false);

            if (($data['status'] ?? false) !== true) {
                $this->logger->warning('TextGears correct status failed', ['response' => $data]);
                return $original;
            }

            $corrected = (string)($data['response']['corrected'] ?? $original);

            // Safeguard: Length check to avoid data loss
            $lenOriginal = mb_strlen($original, 'UTF-8');
            $lenCorrected = mb_strlen($corrected, 'UTF-8');

            if ($lenCorrected < 0.7 * $lenOriginal) {
                $this->logger->warning('TextGears correction too short, falling back to original.', [
                    'original_length' => $lenOriginal,
                    'corrected_length' => $lenCorrected
                ]);
                return $original;
            }

            return $corrected;
        } catch (\Throwable $e) {
            $this->logger->warning('TextGears correct exception', ['error' => $e->getMessage()]);
            return $original;
        }
    }

    /**
     * Returns number of grammar errors using /grammar
     * Defaults to en-US.
     */
    public function grammarErrorCount(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }

        try {
            $resp = $this->http->request('GET', 'https://api.textgears.com/grammar', [
                'query' => [
                    'text' => $text,
                    'language' => self::DEFAULT_LANGUAGE,
                    'key' => $this->apiKey,
                ],
                'timeout' => 8,
            ]);

            $data = $resp->toArray(false);

            if (($data['status'] ?? false) !== true) {
                return 0;
            }

            $errors = $data['response']['errors'] ?? [];
            return is_array($errors) ? count($errors) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
