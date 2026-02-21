<?php

namespace App\Entity;

use App\Repository\CollaboratorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollaboratorRepository::class)]
class Collaborator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'entreprise est obligatoire")]
    #[Assert\Length(max: 255)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "L'adresse email est obligatoire")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide")]
    #[Assert\Length(max: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
        message: "Veuillez saisir une URL valide (ex: www.creaco.com ou https://creaco.com)"
    )]
    #[Assert\Length(max: 255)]
    private ?string $website = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $domain = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $logo = null;

    #[ORM\Column]
    private bool $isPublic = false;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'collaborators')]
    #[ORM\JoinColumn(name: "added_by_user_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Users $addedBy = null;

    #[ORM\Column(length: 50)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    /**
     * @var Collection<int, CollabRequest>
     */
    #[ORM\OneToMany(targetEntity: CollabRequest::class, mappedBy: 'collaborator')]
    private Collection $collabRequests;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'collaborator')]
    private Collection $contracts;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'ACTIVE';
        $this->isPublic = false;
        $this->collabRequests = new ArrayCollection();
        $this->contracts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getAddedBy(): ?Users
    {
        return $this->addedBy;
    }

    public function setAddedBy(?Users $addedBy): static
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    /**
     * Backward compatibility or helper to get the ID
     */
    public function getAddedByUserId(): ?int
    {
        return $this->addedBy ? $this->addedBy->getId() : null;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Check if this collaborator is visible for a specific user.
     * Returns true if the collaborator is public OR if the user is the one who added it.
     */
    public function isVisibleForUser(?int $userId): bool
    {
        return $this->isPublic || ($this->addedBy && $this->addedBy->getId() === $userId);
    }

    /**
     * @return Collection<int, CollabRequest>
     */
    public function getCollabRequests(): Collection
    {
        return $this->collabRequests;
    }

    public function addCollabRequest(CollabRequest $collabRequest): static
    {
        if (!$this->collabRequests->contains($collabRequest)) {
            $this->collabRequests->add($collabRequest);
            $collabRequest->setCollaborator($this);
        }

        return $this;
    }

    public function removeCollabRequest(CollabRequest $collabRequest): static
    {
        if ($this->collabRequests->removeElement($collabRequest)) {
            if ($collabRequest->getCollaborator() === $this) {
                $collabRequest->setCollaborator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setCollaborator($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            if ($contract->getCollaborator() === $this) {
                $contract->setCollaborator(null);
            }
        }

        return $this;
    }
}
