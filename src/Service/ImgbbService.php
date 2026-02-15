<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImgbbService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function upload(UploadedFile $file): ?string
    {
        $response = $this->httpClient->request('POST', 'https://api.imgbb.com/1/upload', [
            'query' => [
                'key' => $this->apiKey,
            ],
            'body' => [
                'image' => base64_encode(file_get_contents($file->getPathname())),
            ],
        ]);

        $data = $response->toArray(false);

        if (isset($data['success']) && $data['success'] === true) {
            return $data['data']['url'];
        }

        return null;
    }
}
