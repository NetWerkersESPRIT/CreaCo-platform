<?php

namespace App\Entity;

use App\Repository\UserCoursProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCoursProgressRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'user_cours_unique', columns: ['user_id', 'cours_id'])]
class UserCoursProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Users $user = null;

    #[ORM\ManyToOne(targetEntity: Cours::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cours $cours = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $progress_percentage = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completed_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column]
    private ?int $total_ressources = 0;

    #[ORM\Column]
    private ?int $opened_ressources = 0;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->progress_percentage = '0.00';
        $this->total_ressources = 0;
        $this->opened_ressources = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;
        return $this;
    }

    public function getProgressPercentage(): ?string
    {
        return $this->progress_percentage;
    }

    public function setProgressPercentage(string $progress_percentage): static
    {
        $this->progress_percentage = $progress_percentage;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function setCompletedAt(?\DateTimeInterface $completed_at): static
    {
        $this->completed_at = $completed_at;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getTotalRessources(): ?int
    {
        return $this->total_ressources;
    }

    public function setTotalRessources(int $total_ressources): static
    {
        $this->total_ressources = $total_ressources;
        return $this;
    }

    public function getOpenedRessources(): ?int
    {
        return $this->opened_ressources;
    }

    public function setOpenedRessources(int $opened_ressources): static
    {
        $this->opened_ressources = $opened_ressources;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdateDate(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function updateProgress(int $openedCount, int $totalCount): void
    {
        $this->opened_ressources = $openedCount;
        $this->total_ressources = $totalCount;
        
        if ($totalCount > 0) {
            $percentage = ($openedCount / $totalCount) * 100;
            $this->progress_percentage = number_format($percentage, 2);
            
            if ($percentage >= 100 && !$this->completed_at) {
                $this->completed_at = new \DateTime();
            }
        } else {
            $this->progress_percentage = '0.00';
        }
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}

