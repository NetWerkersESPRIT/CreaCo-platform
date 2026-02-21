<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $timeLimit = null;

    #[ORM\ManyToOne(inversedBy: 'tasksIssued')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $issuedBy = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Users $assumedBy = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Mission $belongTo = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $completedAt = null;

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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTimeLimit(): ?\DateTime
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?\DateTime $timeLimit): static
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }

    public function getIssuedBy(): ?Users
    {
        return $this->issuedBy;
    }

    public function setIssuedBy(?Users $issuedBy): static
    {
        $this->issuedBy = $issuedBy;

        return $this;
    }

    public function getAssumedBy(): ?Users
    {
        return $this->assumedBy;
    }

    public function setAssumedBy(?Users $assumedBy): static
    {
        $this->assumedBy = $assumedBy;

        return $this;
    }

    public function getBelongTo(): ?Mission
    {
        return $this->belongTo;
    }

    public function setBelongTo(?Mission $belongTo): static
    {
        $this->belongTo = $belongTo;

        return $this;
    }

    #[Assert\Callback]
    public function validateDeadline(ExecutionContextInterface $context): void
    {
        if (!$this->timeLimit) {
            return;
        }

        $now = new \DateTime();

        // If it's a new task, compare with 'now'
        // If it's an existing task, compare with 'now' as well to prevent moving to past
        if ($this->timeLimit < $now) {
            $context->buildViolation('The deadline cannot be in the past.')
                ->atPath('timeLimit')
                ->addViolation();
        }
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
