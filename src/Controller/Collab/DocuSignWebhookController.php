<?php

namespace App\Controller\Collab;

use App\Entity\Contract;
use App\Entity\Notification;
use App\Service\Collaboration\CollaborationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class DocuSignWebhookController extends AbstractController
{
    #[Route('/api/docusign/webhook', name: 'app_api_docusign_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request, EntityManagerInterface $em, LoggerInterface $logger, CollaborationFactory $factory): Response
    {
        // 1. Get raw payload
        $payload = $request->getContent();

        // Basic logging for debugging
        $logger->info('DocuSign Webhook received payload', ['payload_excerpt' => substr($payload, 0, 500)]);

        if (empty($payload)) {
            return new Response('No payload provided', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Depending on DocuSign Account configuration, Connect might send JSON (Connect 2.0) or XML
            // We will attempt to parse XML first since it is the legacy default for eventNotifications
            $envelopeId = null;
            $status = null;

            // Checking if the payload is JSON or XML based on the first char
            if (str_starts_with(trim($payload), '{')) {
                // Parse JSON
                $data = json_decode($payload, true);
                if (isset($data['event']) && isset($data['data']['envelopeId'])) {
                    $status = $data['event']; // e.g., "envelope-completed"
                    $envelopeId = $data['data']['envelopeId'];
                }
            } else {
                // Parse XML
                $xml = simplexml_load_string($payload);
                if ($xml !== false) {
                    $envelopeId = (string) $xml->EnvelopeStatus->EnvelopeID;
                    $status = (string) $xml->EnvelopeStatus->Status; // e.g., "Completed"
                }
            }

            if (!$envelopeId || !$status) {
                $logger->warning('DocuSign Webhook: Could not parse Envelope ID or Status');
                return new Response('Invalid payload format', Response::HTTP_BAD_REQUEST);
            }

            // Target statuses: 'completed' (json) or 'Completed' (xml)
            if (strtolower($status) === 'completed' || strtolower($status) === 'envelope-completed') {

                // Find Contract in database
                $contract = $em->getRepository(Contract::class)->findOneBy([
                    'docusignEnvelopeId' => $envelopeId
                ]);

                if (!$contract) {
                    $logger->error('DocuSign Webhook: Contract not found for envelope ID ' . $envelopeId);
                    return new Response('Contract not found', Response::HTTP_NOT_FOUND);
                }

                // Update the contract status
                if ($contract->getStatus() !== 'SIGNED_BY_COLLABORATOR') {
                    $contract->setStatus('SIGNED_BY_COLLABORATOR');
                    $contract->setSignedByCollaborator(true);
                    $contract->setCollaboratorSignatureDate(new \DateTime());
                    $em->flush();

                    // Optional: Notify Manager/Creator
                    $manager = $contract->getCollabRequest()->getRevisor();
                    if ($manager) {
                        $notification = $factory->createNotification();
                        $notification->setMessage("The partner has signed the contract: '" . $contract->getTitle() . "'. It's fully executed!");
                        $notification->setUserId($manager);
                        $notification->setIsRead(false);
                        $notification->setCreatedAt(new \DateTime());
                        $notification->setType('contract_signed');
                        $notification->setRelatedId($contract->getId());
                        $notification->setTargetUrl($this->generateUrl('app_manager_contract_show', ['id' => $contract->getId()]));

                        $em->persist($notification);
                        $em->flush();
                    }

                    $logger->info("DocuSign Webhook: Successfully marked contract {$contract->getId()} as SIGNED_BY_COLLABORATOR");
                } else {
                    $logger->info("DocuSign Webhook: Contract {$contract->getId()} was already signed.");
                }
            }

        } catch (\Throwable $e) {
            $logger->error('DocuSign Webhook Error: ' . $e->getMessage());
            return new Response('Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Always return 200 OK so DocuSign knows we received it
        return new Response('OK', Response::HTTP_OK);
    }
}
