<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['email'],
    message: 'Cet email existe déjà'
)]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(nullable: true)]
    private ?int $groupid = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numtel = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $reservations;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'targetUsers')]
    private Collection $events;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user_id')]
    private Collection $notifications;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class)]
    private Collection $posts;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(mappedBy: 'assumedBy', targetEntity: Task::class)]
    private Collection $tasks;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(mappedBy: 'issuedBy', targetEntity: Task::class)]
    private Collection $tasksIssued;

    /**
     * @var Collection<int, CollabRequest>
     */
    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: CollabRequest::class)]
    private Collection $collabRequestsCreated;

    /**
     * @var Collection<int, CollabRequest>
     */
    #[ORM\OneToMany(mappedBy: 'revisor', targetEntity: CollabRequest::class)]
    private Collection $collabRequestsRevised;

    /**
     * @var Collection<int, Mission>
     */
    #[ORM\OneToMany(mappedBy: 'assignedBy', targetEntity: Mission::class)]
    private Collection $missionsCreated;

    /**
     * @var Collection<int, Idea>
     */
    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Idea::class)]
    private Collection $ideas;

    /**
     * @var Collection<int, Idea>
     */
    #[ORM\ManyToMany(mappedBy: 'usedBy', targetEntity: Idea::class)]
    private Collection $ideasUsed;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Contract::class)]
    private Collection $contracts;

    /**
     * @var Collection<int, Collaborator>
     */
    #[ORM\OneToMany(mappedBy: 'addedBy', targetEntity: Collaborator::class)]
    private Collection $collaborators;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user')]
    private Collection $comments;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'members')]
    private Collection $groups;

    #[ORM\OneToMany(targetEntity: PostReaction::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $reactions;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->tasksIssued = new ArrayCollection();
        $this->collabRequestsCreated = new ArrayCollection();
        $this->collabRequestsRevised = new ArrayCollection();
        $this->missionsCreated = new ArrayCollection();
        $this->ideas = new ArrayCollection();
        $this->ideasUsed = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->collaborators = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->reactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     * Returns email so the profiler and security tools show the authenticated user's email.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [$this->role ?: 'ROLE_USER'];

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {

    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getGroupid(): ?int
    {
        return $this->groupid;
    }

    public function setGroupid(?int $groupid): static
    {
        $this->groupid = $groupid;

        return $this;
    }

    public function getNumtel(): ?string
    {
        return $this->numtel;
    }

    public function setNumtel(?string $numtel): static
    {
        $this->numtel = $numtel;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function addPoints(int $points): static
    {
        $this->points += $points;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setInitialCreatedAt(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUser($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addTargetUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeTargetUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUserId($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUserId() === $this) {
                $notification->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasksIssued(): Collection
    {
        return $this->tasksIssued;
    }

    /**
     * @return Collection<int, CollabRequest>
     */
    public function getCollabRequestsCreated(): Collection
    {
        return $this->collabRequestsCreated;
    }

    /**
     * @return Collection<int, CollabRequest>
     */
    public function getCollabRequestsRevised(): Collection
    {
        return $this->collabRequestsRevised;
    }

    /**
     * @return Collection<int, Mission>
     */
    public function getMissionsCreated(): Collection
    {
        return $this->missionsCreated;
    }

    /**
     * @return Collection<int, Idea>
     */
    public function getIdeas(): Collection
    {
        return $this->ideas;
    }

    /**
     * @return Collection<int, Idea>
     */
    public function getIdeasUsed(): Collection
    {
        return $this->ideasUsed;
    }
    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    /**
     * @return Collection<int, Collaborator>
     */
    public function getCollaborators(): Collection
    {
        return $this->collaborators;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->addMember($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        if ($this->groups->removeElement($group)) {
            $group->removeMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PostReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(PostReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setUser($this);
        }

        return $this;
    }

    public function removeReaction(PostReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getUser() === $this) {
                $reaction->setUser(null);
            }
        }

        return $this;
    }
}