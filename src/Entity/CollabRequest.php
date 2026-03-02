<?php

namespace App\Entity;

use App\Repository\CollabRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollabRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CollabRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le budget doit être un nombre positif ou nul')]
    private ?string $budget = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan(propertyPath: "startDate", message: "La date de fin doit être postérieure à la date de début")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 50)]
    private string $status = 'PENDING';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deliverables = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $respondedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $aiSuccessScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $aiClarityScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $aiBudgetRealismScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $aiTimelineFeasibilityScore = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $aiFlags = null;

    #[ORM\Column]
    private int $aiUsageCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiOriginalContent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiRephrasedContent = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'collabRequestsCreated')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Users $creator = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'collabRequestsRevised')]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Assert\NotNull(message: 'Veuillez sélectionner un manager pour révision')]
    private ?Users $revisor = null;

    #[ORM\ManyToOne(targetEntity: Collaborator::class, inversedBy: 'collabRequests')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Collaborator $collaborator = null;

    #[ORM\OneToOne(targetEntity: Contract::class, mappedBy: 'collabRequest')]
    private ?Contract $contract = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'PENDING';
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBudget(): ?string
    {
        return $this->budget;
    }

    public function setBudget(?string $budget): static
    {
        $this->budget = $budget;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    public function getDeliverables(): ?string
    {
        return $this->deliverables;
    }

    public function setDeliverables(?string $deliverables): static
    {
        $this->deliverables = $deliverables;

        return $this;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): static
    {
        $this->paymentTerms = $paymentTerms;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRespondedAt(): ?\DateTimeInterface
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeInterface $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }

    public function getAiSuccessScore(): ?int
    {
        return $this->aiSuccessScore;
    }

    public function setAiSuccessScore(?int $aiSuccessScore): static
    {
        $this->aiSuccessScore = $aiSuccessScore;

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

    public function getRevisor(): ?Users
    {
        return $this->revisor;
    }

    public function setRevisor(?Users $revisor): static
    {
        $this->revisor = $revisor;

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

    public function getAiClarityScore(): ?int
    {
        return $this->aiClarityScore;
    }

    public function setAiClarityScore(?int $aiClarityScore): static
    {
        $this->aiClarityScore = $aiClarityScore;
        return $this;
    }

    public function getAiBudgetRealismScore(): ?int
    {
        return $this->aiBudgetRealismScore;
    }

    public function setAiBudgetRealismScore(?int $aiBudgetRealismScore): static
    {
        $this->aiBudgetRealismScore = $aiBudgetRealismScore;
        return $this;
    }

    public function getAiTimelineFeasibilityScore(): ?int
    {
        return $this->aiTimelineFeasibilityScore;
    }

    public function setAiTimelineFeasibilityScore(?int $aiTimelineFeasibilityScore): static
    {
        $this->aiTimelineFeasibilityScore = $aiTimelineFeasibilityScore;
        return $this;
    }

    public function getAiFlags(): ?array
    {
        return $this->aiFlags;
    }

    public function setAiFlags(?array $aiFlags): static
    {
        $this->aiFlags = $aiFlags;
        return $this;
    }

    public function getAiUsageCount(): int
    {
        return $this->aiUsageCount;
    }

    public function setAiUsageCount(int $aiUsageCount): static
    {
        $this->aiUsageCount = $aiUsageCount;
        return $this;
    }

    public function incrementAiUsageCount(): static
    {
        $this->aiUsageCount++;
        return $this;
    }

    public function getAiOriginalContent(): ?string
    {
        return $this->aiOriginalContent;
    }

    public function setAiOriginalContent(?string $aiOriginalContent): static
    {
        $this->aiOriginalContent = $aiOriginalContent;
        return $this;
    }

    public function getAiRephrasedContent(): ?string
    {
        return $this->aiRephrasedContent;
    }

    public function setAiRephrasedContent(?string $aiRephrasedContent): static
    {
        $this->aiRephrasedContent = $aiRephrasedContent;
        return $this;
    }

    public function getOverallAcceptancePrediction(): int
    {
        if ($this->aiClarityScore === null || $this->aiBudgetRealismScore === null || $this->aiTimelineFeasibilityScore === null) {
            return $this->aiSuccessScore ?? 0;
        }
        return (int) (($this->aiClarityScore + $this->aiBudgetRealismScore + $this->aiTimelineFeasibilityScore) / 3);
    }
}
