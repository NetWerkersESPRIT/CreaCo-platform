<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$cloudName = 'Root';
$apiKey = 'y599614139576625';
$apiSecret = 'ZPtx3fwLRF9ug1rhrZImkfxjtUw';

$timestamp = time();
$signature = sha1("timestamp={$timestamp}{$apiSecret}");

$client = HttpClient::create();

try {
    echo "Testing Cloudinary Upload...\n";
    echo "Cloud Name: $cloudName\n";
    echo "Timestamp: $timestamp\n";
    echo "Signature: $signature\n";

    // Create a dummy PDF
    file_put_contents('test.pdf', '%PDF-1.4 test');

    $response = $client->request('POST',
        "https://api.cloudinary.com/v1_1/{$cloudName}/raw/upload",
        [
            'body' => [
                'file' => fopen('test.pdf', 'r'),
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ],
        ]
    );

    $status = $response->getStatusCode();
    $data = $response->toArray(false);

    echo "Status: $status\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

    unlink('test.pdf');
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
