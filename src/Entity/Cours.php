<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    // TITRE DU COURS
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
    min: 3,
    max: 255,
    minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
    maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    // Format du titre
    

    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    // Longueur de la description
    #[Assert\Length(
    min: 10,
    max: 2000,
    minMessage: "La description doit faire au moins {{ limit }} caractères",
    maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
)]

    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]

    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_de_creation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_de_modification = null;

    #[ORM\ManyToOne(inversedBy: 'cours')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La catégorie est obligatoire")]
    private ?CategorieCours $categorie = null;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Ressource::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $ressources;

    #[ORM\Column(nullable: true)]
    private ?int $views = null;

    public function __construct()
    {
        $this->ressources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCategorie(): ?CategorieCours
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieCours $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, Ressource>
     */
    public function getRessources(): Collection
    {
        return $this->ressources;
    }

    public function addRessource(Ressource $ressource): static
    {
        if (!$this->ressources->contains($ressource)) {
            $this->ressources->add($ressource);
            $ressource->setCours($this);
        }

        return $this;
    }

    public function removeRessource(Ressource $ressource): static
    {
        if ($this->ressources->removeElement($ressource)) {
            // set the owning side to null (unless already changed)
            if ($ressource->getCours() === $this) {
                $ressource->setCours(null);
            }
        }


        return $this;
    }
    public function getDateDeCreation(): ?\DateTimeInterface

    {
        return $this->date_de_creation;
    }

    public function setDateDeCreation(\DateTimeInterface $date_de_creation): static
    {
        $this->date_de_creation = $date_de_creation;

        return $this;
    }

    public function getDateDeModification(): ?\DateTimeInterface
    {
        return $this->date_de_modification;
    }

    public function setDateDeModification(?\DateTimeInterface $date_de_modification): static
    {
        $this->date_de_modification = $date_de_modification;

        return $this;
    }

    #[ORM\PrePersist]
    public function setInitialDates(): void
    {
        $this->date_de_creation = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdateDate(): void
    {
        $this->date_de_modification = new \DateTime();
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(?int $views): static
    {
        $this->views = $views;

        return $this;
    }
}
