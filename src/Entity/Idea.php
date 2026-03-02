<?php

namespace App\Entity;

use App\Repository\IdeaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IdeaRepository::class)]
class Idea
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
    private ?string $category = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $lastUsed = null;

    #[ORM\ManyToOne(inversedBy: 'ideas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $creator = null;

    /**
     * @var Collection<int, Mission>
     */
    #[ORM\OneToMany(targetEntity: Mission::class, mappedBy: 'implementIdea')]
    private Collection $missions;

    /**
     * @var Collection<int, IdeaUsage>
     */
    #[ORM\OneToMany(targetEntity: IdeaUsage::class, mappedBy: 'Idea')]
    private Collection $ideaUsages;

    /**
     * @var Collection<int, Users>
     */
    #[ORM\ManyToMany(targetEntity: Users::class, inversedBy: 'ideasUsed')]
    private Collection $usedBy;



    public function __construct()
    {
        $this->missions = new ArrayCollection();
        $this->ideaUsages = new ArrayCollection();
        $this->usedBy = new ArrayCollection();
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

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

    public function getLastUsed(): ?\DateTime
    {
        return $this->lastUsed;
    }

    public function setLastUsed(?\DateTime $lastUsed): static
    {
        $this->lastUsed = $lastUsed;

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

    /**
     * @return Collection<int, Mission>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setImplementIdea($this);
        }

        return $this;
    }

    public function removeMission(Mission $mission): static
    {
        if ($this->missions->removeElement($mission)) {
            // set the owning side to null (unless already changed)
            if ($mission->getImplementIdea() === $this) {
                $mission->setImplementIdea(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, IdeaUsage>
     */
    public function getIdeaUsages(): Collection
    {
        return $this->ideaUsages;
    }

    public function addIdeaUsage(IdeaUsage $ideaUsage): static
    {
        if (!$this->ideaUsages->contains($ideaUsage)) {
            $this->ideaUsages->add($ideaUsage);
            $ideaUsage->setIdea($this);
        }

        return $this;
    }

    public function removeIdeaUsage(IdeaUsage $ideaUsage): static
    {
        if ($this->ideaUsages->removeElement($ideaUsage)) {
            // set the owning side to null (unless already changed)
            if ($ideaUsage->getIdea() === $this) {
                $ideaUsage->setIdea(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Users>
     */
    public function getUsedBy(): Collection
    {
        return $this->usedBy;
    }

    public function addUsedBy(Users $user): static
    {
        if (!$this->usedBy->contains($user)) {
            $this->usedBy->add($user);
        }

        return $this;
    }

    public function removeUsedBy(Users $user): static
    {
        $this->usedBy->removeElement($user);

        return $this;
    }

}
