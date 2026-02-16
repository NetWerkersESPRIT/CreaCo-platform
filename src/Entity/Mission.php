<?php

namespace App\Entity;

use App\Repository\MissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
#[Assert\Callback([self::class, 'validateMissionDate'])]
class Mission
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
    private ?\DateTime $lastUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Idea $implementIdea = null;

    #[ORM\ManyToOne(inversedBy: 'missionsCreated')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $assignedBy = null;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'belongTo')]
    private Collection $tasks;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $missionDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getLastUpdate(): ?\DateTime
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(?\DateTime $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function getImplementIdea(): ?Idea
    {
        return $this->implementIdea;
    }

    public function setImplementIdea(?Idea $implementIdea): static
    {
        $this->implementIdea = $implementIdea;

        return $this;
    }

    public function getAssignedBy(): ?Users
    {
        return $this->assignedBy;
    }

    public function setAssignedBy(?Users $assignedBy): static
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setBelongTo($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getBelongTo() === $this) {
                $task->setBelongTo(null);
            }
        }

        return $this;
    }

    public function getMissionDate(): ?\DateTimeInterface
    {
        return $this->missionDate;
    }

    public function setMissionDate(?\DateTimeInterface $missionDate): static
    {
        $this->missionDate = $missionDate;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public static function validateMissionDate(mixed $object, ExecutionContextInterface $context): void
    {
        if (!$object instanceof self) {
            return;
        }

        $now = new \DateTimeImmutable();
        $createdAt = $object->getCreatedAt() ?? $now;

        if ($object->getMissionDate() !== null) {
            // If it's a new object (id is null), we should probably compare with 'now'
            // to prevent selecting past times during creation.
            $comparisonDate = ($object->getId() === null) ? $now : $createdAt;

            if ($object->getMissionDate() < $comparisonDate) {
                $context->buildViolation('The mission date and time cannot be earlier than its creation date.')
                    ->atPath('missionDate')
                    ->addViolation();
            }
        }
    }
}
