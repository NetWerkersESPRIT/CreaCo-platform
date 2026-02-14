<?php

namespace App\Service;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveUploader
{
    private Drive $drive;

    public function __construct(
        private string $credentialsPath,
        private string $folderId
    ) {
        $client = new Client();
        $client->setAuthConfig($this->credentialsPath);
        $client->setScopes([Drive::DRIVE_FILE]);

        $this->drive = new Drive($client);
    }

    public function uploadPdf(string $localPath, string $fileName): array
    {
        $meta = new DriveFile([
            'name' => $fileName,
            'parents' => [$this->folderId],
        ]);

        $created = $this->drive->files->create($meta, [
            'data' => file_get_contents($localPath),
            'mimeType' => 'application/pdf',
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink',
        ]);

        return ['id' => $created->id, 'link' => $created->webViewLink];
    }
}
