<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Name is required")]
    #[Assert\Length(min: 3, minMessage: "Name must be at least 3 characters long")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Description is required")]
    #[Assert\Length(min: 10, minMessage: "Description must be at least 10 characters long")]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Type is required")]
    private ?string $type = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "Category is required")]
    private ?string $category = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "Date is required")]
    #[Assert\GreaterThan("today", message: "The date must be later than today")]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "Time is required")]
    private ?\DateTime $time = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Organizer is required")]
    private ?string $organizer = null;

    #[ORM\Column]
    private ?bool $isForAllUsers = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "Meeting link must be a valid URL")]
    private ?string $meetingLink = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $platform = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "Google Maps link must be a valid URL")]
    private ?string $googleMapsLink = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "Capacity must be a positive number")]
    private ?int $capacity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Contact information is required")]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $reservations;

    /**
     * @var Collection<int, Users>
     */
    #[ORM\ManyToMany(targetEntity: Users::class, inversedBy: 'events')]
    private Collection $targetUsers;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->targetUsers = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getOrganizer(): ?string
    {
        return $this->organizer;
    }

    public function setOrganizer(string $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function isForAllUsers(): ?bool
    {
        return $this->isForAllUsers;
    }

    public function setIsForAllUsers(bool $isForAllUsers): static
    {
        $this->isForAllUsers = $isForAllUsers;

        return $this;
    }

    public function getMeetingLink(): ?string
    {
        return $this->meetingLink;
    }

    public function setMeetingLink(?string $meetingLink): static
    {
        $this->meetingLink = $meetingLink;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): static
    {
        $this->platform = $platform;

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

    public function getGoogleMapsLink(): ?string
    {
        return $this->googleMapsLink;
    }

    public function setGoogleMapsLink(?string $googleMapsLink): static
    {
        $this->googleMapsLink = $googleMapsLink;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
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
            $reservation->setEvent($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getEvent() === $this) {
                $reservation->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Users>
     */
    public function getTargetUsers(): Collection
    {
        return $this->targetUsers;
    }

    public function addTargetUser(Users $targetUser): static
    {
        if (!$this->targetUsers->contains($targetUser)) {
            $this->targetUsers->add($targetUser);
        }

        return $this;
    }

    public function removeTargetUser(Users $targetUser): static
    {
        $this->targetUsers->removeElement($targetUser);

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getValidatedReservationsCount(): int
    {
        $count = 0;
        foreach ($this->reservations as $reservation) {
            if ($reservation->getStatus() === 'validated') {
                $count++;
            }
        }
        return $count;
    }

    public function getPendingReservationsCount(): int
    {
        $count = 0;
        foreach ($this->reservations as $reservation) {
            if ($reservation->getStatus() === 'pending') {
                $count++;
            }
        }
        return $count;
    }
}
