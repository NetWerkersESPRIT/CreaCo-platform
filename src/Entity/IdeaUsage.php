<?php

namespace App\Entity;

use App\Repository\IdeaUsageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IdeaUsageRepository::class)]
class IdeaUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ideaUsages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $User = null;

    #[ORM\ManyToOne(inversedBy: 'ideaUsages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Idea $Idea = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateUsed = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->User;
    }

    public function setUser(?Users $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getIdea(): ?Idea
    {
        return $this->Idea;
    }

    public function setIdea(?Idea $Idea): static
    {
        $this->Idea = $Idea;

        return $this;
    }

    public function getDateUsed(): ?\DateTimeImmutable
    {
        return $this->dateUsed;
    }

    public function setDateUsed(\DateTimeImmutable $dateUsed): static
    {
        $this->dateUsed = $dateUsed;

        return $this;
    }
}
