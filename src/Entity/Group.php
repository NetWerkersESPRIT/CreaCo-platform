<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $owner = null;

    /**
     * @var Collection<int, Users>
     */
    #[ORM\ManyToMany(targetEntity: Users::class, inversedBy: 'groups')]
    #[ORM\JoinTable(name: 'group_user')]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOwner(): ?Users
    {
        return $this->owner;
    }

    public function setOwner(?Users $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Users>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Users $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }

        return $this;
    }

    public function removeMember(Users $member): static
    {
        $this->members->removeElement($member);

        return $this;
    }
}
