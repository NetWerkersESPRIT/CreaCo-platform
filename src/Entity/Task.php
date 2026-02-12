<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

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
    private ?\DateTimeImmutable $cratedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $timeTlimit = null;

    #[ORM\ManyToOne(inversedBy: 'tasksIssued')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $issuedBy = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Users $assumedBy = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Mission $belongTo = null;

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

    public function getCratedAt(): ?\DateTimeImmutable
    {
        return $this->cratedAt;
    }

    public function setCratedAt(\DateTimeImmutable $cratedAt): static
    {
        $this->cratedAt = $cratedAt;

        return $this;
    }

    public function getTimeTlimit(): ?\DateTime
    {
        return $this->timeTlimit;
    }

    public function setTimeTlimit(?\DateTime $timeTlimit): static
    {
        $this->timeTlimit = $timeTlimit;

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
}
