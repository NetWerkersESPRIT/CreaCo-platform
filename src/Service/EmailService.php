<?php

namespace App\Service;

use App\Entity\Contract;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    public function sendFormalContractNotice(Contract $contract): void
    {
        $collaborator = $contract->getCollaborator();
        $collabRequest = $contract->getCollabRequest();
        $manager = $collabRequest->getRevisor();

        // Calculate deadline (48h from now)
        $deadline = new \DateTime('+48 hours');

        // Signature URL
        $signatureUrl = $this->urlGenerator->generate('app_public_contract_signature_view', [
            'contractNumber' => $contract->getContractNumber(),
            'token' => $contract->getSignatureToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from('ricranim@gmail.com') // Official CreaCo Emitter
            ->to($collaborator->getEmail())
            ->subject('CreaCo // Formal Collaboration Agreement - ' . $contract->getTitle())
            ->htmlTemplate('emails/contract_formal_notice.html.twig')
            ->context([
                'contract' => $contract,
                'collaborator' => $collaborator,
                'collabRequest' => $collabRequest,
                'manager' => $manager,
                'deadline' => $deadline,
                'signatureUrl' => $signatureUrl,
            ]);

        $this->mailer->send($email);
    }
}
