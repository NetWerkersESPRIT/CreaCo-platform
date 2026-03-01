<?php

namespace App\Service;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\CarbonCopy;
use DocuSign\eSign\Model\EventNotification;
use DocuSign\eSign\Model\EnvelopeEvent;
use PHPUnit\Util\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DocuSignService
{
    private string $clientId;
    private string $userId;
    private string $accountId;
    private string $privateKeyPath;
    private ApiClient $apiClient;

    public function __construct(ParameterBagInterface $params)
    {
        // Read parameters (you will need to map these in services.yaml or configure them via attributes)
        $this->clientId = $_ENV['DOCUSIGN_CLIENT_ID'] ?? '';
        $this->userId = $_ENV['DOCUSIGN_USER_ID'] ?? '';
        $this->accountId = $_ENV['DOCUSIGN_ACCOUNT_ID'] ?? '';

        $envKeyPath = $_ENV['DOCUSIGN_PRIVATE_KEY_PATH'] ?? '';
        $this->privateKeyPath = str_replace('%kernel.project_dir%', $params->get('kernel.project_dir'), $envKeyPath);

        $config = new Configuration();
        $config->setHost('https://demo.docusign.net/restapi');

        $this->apiClient = new ApiClient($config);

        // Use demo auth server and rest API
        $this->apiClient->getOAuth()->setOAuthBasePath("account-d.docusign.com");
    }

    private function authenticate(): void
    {
        try {
            if (!file_exists($this->privateKeyPath)) {
                throw new \Exception("DocuSign private key not found at: {$this->privateKeyPath}");
            }

            $privateKey = file_get_contents($this->privateKeyPath);
            $scopes = ['signature', 'impersonation'];

            // Get JWT access token
            $response = $this->apiClient->requestJWTUserToken(
                $this->clientId,
                $this->userId,
                $privateKey,
                $scopes
            );

            // Configure api client to use this token
            if ($response && isset($response[0])) {
                $token = $response[0]->getAccessToken();
                $this->apiClient->getConfig()->addDefaultHeader('Authorization', 'Bearer ' . $token);
            } else {
                throw new \Exception("Failed to acquire access token from DocuSign.");
            }
        } catch (\Throwable $th) {
            throw new \Exception("DocuSign Authentication Error: " . $th->getMessage());
        }
    }

    public function sendContractToCollaborator(
        string $collaboratorEmail,
        string $collaboratorName,
        string $contractTitle,
        string $contractDetails,
        string $contractRef,
        string $returnUrl
    ): ?string {

        // 1. Authenticate
        $this->authenticate();

        // 2. Generate PDF Document locally or create an HTML Representation
        // For simplicity and immediate delivery, we'll create an HTML representation of the contract
        // In a complex app you'd render a Twig template to PDF using Snappy/Dompdf first

        $htmlContent = "
            <html>
                <body style='font-family: Arial, sans-serif; margin: 40px;'>
                    <h1 style='color: #4f46e5;'>Digital Service Contract: {$contractTitle}</h1>
                    <p><strong>Reference:</strong> {$contractRef}</p>
                    <p><strong>Prepared for:</strong> {$collaboratorName}</p>
                    <hr>
                    <div style='background-color: #f8fafc; padding: 20px; border-radius: 8px;'>
                        <p><strong>Mission Summary:</strong></p>
                        <p>{$contractDetails}</p>
                    </div>
                    <br><br>
                    <p>By signing this document, you agree to the conditions defined by CreaCo Ecosystem.</p>
                    <br><br>
                    <p>Collaborator Signature:</p>
                    <div style='margin-top:20px; color:white;'>/sn1/</div>
                </body>
            </html>
        ";

        $document = new Document([
            'document_base64' => base64_encode($htmlContent),
            'name' => 'CreaCo_Contract_' . $contractRef,
            'file_extension' => 'html',
            'document_id' => '1'
        ]);

        // 3. Create Signer
        $signer = new Signer([
            'email' => $collaboratorEmail,
            'name' => $collaboratorName,
            'recipient_id' => '1',
            'routing_order' => '1'
        ]);

        // 4. Create Tabs (Signature location)
        $signHere = new SignHere([
            'anchor_string' => '/sn1/',
            'anchor_units' => 'pixels',
            'anchor_y_offset' => '10',
            'anchor_x_offset' => '20'
        ]);

        $signer->setTabs(new Tabs(['sign_here_tabs' => [$signHere]]));

        // 5. Build EventNotification (Webhook)
        $webhookUrl = $_ENV['DOCUSIGN_WEBHOOK_URL'] ?? null;
        $eventNotification = null;

        if ($webhookUrl) {
            $envelopeEvent = new EnvelopeEvent([
                'envelope_event_status_code' => 'completed',
                'include_documents' => 'false' // Can be true if we need the signed PDF document later
            ]);

            $eventNotification = new EventNotification([
                'url' => $webhookUrl,
                'logging_enabled' => 'true',
                'require_acknowledgment' => 'true',
                'use_soap_interface' => 'false',
                'include_certificate_with_soap' => 'false',
                'sign_message_with_x509_cert' => 'false',
                'include_documents' => 'false',
                'include_envelope_void_reason' => 'true',
                'include_time_zone' => 'true',
                'include_sender_account_as_custom_field' => 'true',
                'include_document_fields' => 'true',
                'include_certificate_of_completion' => 'false',
                'envelope_events' => [$envelopeEvent]
            ]);
        }

        // 6. Build Envelope
        $envelopeDefinition = new EnvelopeDefinition([
            'email_subject' => "Signature Required: CreaCo Contract {$contractRef}",
            'documents' => [$document],
            'recipients' => new Recipients(['signers' => [$signer]]),
            'status' => "sent", // Send immediately
            'event_notification' => $eventNotification
        ]);

        // 7. Send Envelope
        $envelopeApi = new EnvelopesApi($this->apiClient);

        try {
            $result = $envelopeApi->createEnvelope($this->accountId, $envelopeDefinition);
            return $result->getEnvelopeId(); // Returns the generated envelope ID
        } catch (\DocuSign\eSign\Client\ApiException $e) {
            $error = $e->getResponseBody();
            throw new \Exception("DocuSign API Error: " . json_encode($error));
        } catch (\Throwable $th) {
            throw new \Exception("Failed to send envelope: " . $th->getMessage());
        }
    }
}
