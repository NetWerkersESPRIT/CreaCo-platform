<?php

namespace App\Entity;

use App\Repository\CategorieCoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategorieCoursRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CategorieCours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // NOM DE LA CATEGORIE
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire")]
    // Longueur du nom
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    // Format du nom
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9\s\-]+$/",
        message: "Le nom ne peut contenir que des lettres, chiffres, espaces et tirets"
    )]

    private ?string $nom = null;

    // DESCRIPTION DE LA CATEGORIE
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // DATE DE CREATION DE LA CATEGORIE
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_de_creation = null;

    // DATE DE MODIFICATION DE LA CATEGORIE
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_de_modification = null;

    // COURS DE LA CATEGORIE
    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Cours::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $cours;

    // CONSTRUCTEUR
    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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
    // GET COURS DE LA CATEGORIE
    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }
    // ADD COURS DE LA CATEGORIE
    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setCategorie($this);
        }

        return $this;
    }
    // REMOVE COURS DE LA CATEGORIE
    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // set the owning side to null (unless already changed)
            if ($cour->getCategorie() === $this) {
                $cour->setCategorie(null);
            }
        }

        return $this;
    }
    // GET DATE DE CREATION DE LA CATEGORIE
    public function getDateDeCreation(): ?\DateTimeInterface
    {
        return $this->date_de_creation;
    }
    // SET DATE DE CREATION DE LA CATEGORIE
    public function setDateDeCreation(\DateTimeInterface $date_de_creation): static
    {
        $this->date_de_creation = $date_de_creation;

        return $this;
    }
    // GET DATE DE MODIFICATION DE LA CATEGORIE
    public function getDateDeModification(): ?\DateTimeInterface
    {
        return $this->date_de_modification;
    }
    // SET DATE DE MODIFICATION DE LA CATEGORIE
    public function setDateDeModification(?\DateTimeInterface $date_de_modification): static
    {
        $this->date_de_modification = $date_de_modification;

        return $this;
    }
    // SET DATE DE CREATION DE LA CATEGORIE
    #[ORM\PrePersist]
    public function setInitialDates(): void
    {
        $this->date_de_creation = new \DateTime();
    }
    // SET DATE DE MODIFICATION DE LA CATEGORIE
    #[ORM\PreUpdate]
    public function setUpdateDate(): void
    {
        $this->date_de_modification = new \DateTime();
    }
}
