<?php

namespace App\Entity;

use App\Repository\UserRessourceProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRessourceProgressRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'user_ressource_unique', columns: ['user_id', 'ressource_id'])]
class UserRessourceProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Users $user = null;

    #[ORM\ManyToOne(targetEntity: Ressource::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ressource $ressource = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'not_opened'; // 'not_opened', 'opened'

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $opened_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->status = 'not_opened';
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

    public function getRessource(): ?Ressource
    {
        return $this->ressource;
    }

    public function setRessource(?Ressource $ressource): static
    {
        $this->ressource = $ressource;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getOpenedAt(): ?\DateTimeInterface
    {
        return $this->opened_at;
    }

    public function setOpenedAt(?\DateTimeInterface $opened_at): static
    {
        $this->opened_at = $opened_at;
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

    #[ORM\PreUpdate]
    public function setUpdateDate(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function markAsOpened(): void
    {
        if ($this->status !== 'opened') {
            $this->status = 'opened';
            $this->opened_at = new \DateTime();
        }
    }

    public function isOpened(): bool
    {
        return $this->status === 'opened';
    }
}

