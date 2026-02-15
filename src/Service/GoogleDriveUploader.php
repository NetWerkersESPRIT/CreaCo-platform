<?php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GoogleDriveUploader
{
    private Drive $drive;

    public function __construct(
        private string $googleDriveFolderId,
        private string $projectDir
    ) {
        $client = new GoogleClient();
        $client->setAuthConfig($this->projectDir . '/config/google/credentials.json');
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');

        $tokenPath = $this->projectDir . '/config/google/token.json';
        if (!file_exists($tokenPath)) {
            throw new \RuntimeException('Google Drive not connected. Visit /google/oauth/login first.');
        }

        $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if (!$refreshToken) {
                throw new \RuntimeException('Missing refresh token. Reconnect: /google/oauth/login');
            }
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            file_put_contents($tokenPath, json_encode($client->getAccessToken(), JSON_PRETTY_PRINT));
        }

        $this->drive = new Drive($client);
    }

    public function upload(UploadedFile $file, ?string $filename = null): array
    {
        $metadata = new DriveFile([
            'name' => $filename ?: $file->getClientOriginalName(),
            'parents' => [$this->googleDriveFolderId],
        ]);

        $created = $this->drive->files->create($metadata, [
            'data' => file_get_contents($file->getPathname()),
            'mimeType' => $file->getMimeType() ?: 'application/pdf',
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink',
        ]);

        // ✅ Set public permissions (anyone with the link can view)
        $permission = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        $this->drive->permissions->create($created->id, $permission);

        return ['id' => $created->id, 'link' => $created->webViewLink];
    }
}
