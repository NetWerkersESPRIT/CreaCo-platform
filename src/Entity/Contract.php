<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $contractNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le montant doit être un nombre positif ou nul')]
    private ?string $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(length: 50)]
    private string $status = 'DRAFT';

    #[ORM\Column]
    private bool $signedByCreator = false;

    #[ORM\Column]
    private bool $signedByCollaborator = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creatorSignatureDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $collaboratorSignatureDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentSchedule = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $confidentialityClause = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationTerms = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signatureToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\OneToOne(targetEntity: CollabRequest::class, inversedBy: 'contract')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?CollabRequest $collabRequest = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    private ?Users $creator = null;

    #[ORM\ManyToOne(targetEntity: Collaborator::class, inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Collaborator $collaborator = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'DRAFT';
        $this->signedByCreator = false;
        $this->signedByCollaborator = false;
        $this->generateContractNumber();
        $this->generateSignatureToken();
    }

    private function generateContractNumber(): void
    {
        $year = date('Y');
        $uniqueId = bin2hex(random_bytes(6));
        $this->contractNumber = sprintf('CONT-%s-%s', $year, strtoupper($uniqueId));
    }

    private function generateSignatureToken(): void
    {
        $this->signatureToken = bin2hex(random_bytes(32));
    }

    public function isFullySigned(): bool
    {
        return $this->signedByCreator && $this->signedByCollaborator;
    }

    public function canBeSignedByCreator(): bool
    {
        return !$this->signedByCreator && in_array($this->status, ['DRAFT', 'PENDING_SIGNATURES'], true);
    }

    public function canBeSignedByCollaborator(): bool
    {
        return !$this->signedByCollaborator && $this->status === 'SENT_TO_COLLABORATOR';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isSignedByCreator(): bool
    {
        return $this->signedByCreator;
    }

    public function setSignedByCreator(bool $signedByCreator): static
    {
        $this->signedByCreator = $signedByCreator;

        return $this;
    }

    public function isSignedByCollaborator(): bool
    {
        return $this->signedByCollaborator;
    }

    public function setSignedByCollaborator(bool $signedByCollaborator): static
    {
        $this->signedByCollaborator = $signedByCollaborator;

        return $this;
    }

    public function getCreatorSignatureDate(): ?\DateTimeInterface
    {
        return $this->creatorSignatureDate;
    }

    public function setCreatorSignatureDate(?\DateTimeInterface $creatorSignatureDate): static
    {
        $this->creatorSignatureDate = $creatorSignatureDate;

        return $this;
    }

    public function getCollaboratorSignatureDate(): ?\DateTimeInterface
    {
        return $this->collaboratorSignatureDate;
    }

    public function setCollaboratorSignatureDate(?\DateTimeInterface $collaboratorSignatureDate): static
    {
        $this->collaboratorSignatureDate = $collaboratorSignatureDate;

        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): static
    {
        $this->terms = $terms;

        return $this;
    }

    public function getPaymentSchedule(): ?string
    {
        return $this->paymentSchedule;
    }

    public function setPaymentSchedule(?string $paymentSchedule): static
    {
        $this->paymentSchedule = $paymentSchedule;

        return $this;
    }

    public function getConfidentialityClause(): ?string
    {
        return $this->confidentialityClause;
    }

    public function setConfidentialityClause(?string $confidentialityClause): static
    {
        $this->confidentialityClause = $confidentialityClause;

        return $this;
    }

    public function getCancellationTerms(): ?string
    {
        return $this->cancellationTerms;
    }

    public function setCancellationTerms(?string $cancellationTerms): static
    {
        $this->cancellationTerms = $cancellationTerms;

        return $this;
    }

    public function getSignatureToken(): ?string
    {
        return $this->signatureToken;
    }

    public function setSignatureToken(?string $signatureToken): static
    {
        $this->signatureToken = $signatureToken;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getCollabRequest(): ?CollabRequest
    {
        return $this->collabRequest;
    }

    public function setCollabRequest(?CollabRequest $collabRequest): static
    {
        $this->collabRequest = $collabRequest;

        return $this;
    }

    public function getCreator(): ?Users
    {
        return $this->creator;
    }

    public function setCreator(?Users $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCollaborator(): ?Collaborator
    {
        return $this->collaborator;
    }

    public function setCollaborator(?Collaborator $collaborator): static
    {
        $this->collaborator = $collaborator;

        return $this;
    }
}
