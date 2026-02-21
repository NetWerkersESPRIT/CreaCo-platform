<?php

namespace App\Entity;


use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;


#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ["draft", "published", "solved", "pending", "refused"],
        message: "Statut invalide. Choisis: draft, published, solved, pending, refused."
    )]
    private ?string $status = "published";

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refusalReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $pinned = false;

    #[ORM\Column(options: ["default" => false])]
    private bool $isCommentLocked = false;

    #[ORM\Column(options: ["default" => true])]
    private bool $isModerationNotified = true;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins {{ limit }} caractères.")]
    private ?string $content = null;

    #[Assert\File(
        maxSize: "5M",
        mimeTypes: ["image/jpeg", "image/png", "image/webp"],
        mimeTypesMessage: "Veuillez uploader une image valide (JPEG, PNG, WEBP)"
    )]
    private ?UploadedFile $imageFile = null;

    #[Assert\File(
        maxSize: "10M",
        mimeTypes: ["application/pdf"],
        mimeTypesMessage: "Veuillez uploader un fichier PDF valide"
    )]
    private ?UploadedFile $pdfFile = null;

    #[ORM\OneToOne(targetEntity: Comment::class, cascade: ['persist', 'remove'])]
    private ?Comment $solution = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: true, name: "user_id", referencedColumnName: "id")]
    private ?Users $user = null;



    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfName = null;

    #[ORM\Column(options: ["default" => 0])]
    private int $likes = 0;

    #[ORM\Column(length: 255, nullable: true)]
private ?string $pdfDriveFileId = null;

#[ORM\Column(length: 255, nullable: true)]
private ?string $pdfDriveLink = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToOne(mappedBy: 'post', targetEntity: Conversation::class, cascade: ['persist', 'remove'])]
    private ?Conversation $conversation = null;

    #[ORM\Column(options: ["default" => false])]
    private bool $isProfane = false;

    #[ORM\Column(options: ["default" => 0])]
    private int $profaneWords = 0;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTime(); 
        $this->likes = 0;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getSolution(): ?Comment
    {
        return $this->solution;
    }

    public function setSolution(?Comment $solution): static
    {
        $this->solution = $solution;
        return $this;
    }

    public function isPinned(): ?bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): static
    {
        $this->pinned = $pinned;
        return $this;
    }

    public function isCommentLocked(): bool
    {
        return $this->isCommentLocked;
    }

    public function setIsCommentLocked(bool $isCommentLocked): static
    {
        $this->isCommentLocked = $isCommentLocked;
        return $this;
    }

    public function setCommentLock(bool $isCommentLocked): static
    {
        $this->isCommentLocked = $isCommentLocked;
        return $this;
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



    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getPdfName(): ?string
    {
        return $this->pdfName;
    }

    public function setPdfName(?string $pdfName): static
    {
        $this->pdfName = $pdfName;
        return $this;
    }

    public function getImageFile(): ?UploadedFile
    {
        return $this->imageFile;
    }

    public function setImageFile(?UploadedFile $imageFile): static
    {
        $this->imageFile = $imageFile;
        return $this;
    }

    public function getPdfFile(): ?UploadedFile
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?UploadedFile $pdfFile): static
    {
        $this->pdfFile = $pdfFile;
        return $this;
    }

    public function getLikes(): int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): static
    {
        $this->likes = $likes;
        return $this;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }
    public function getRefusalReason(): ?string
    {
        return $this->refusalReason;
    }

    public function setRefusalReason(?string $refusalReason): static
    {
        $this->refusalReason = $refusalReason;
        return $this;
    }

    public function isModerationNotified(): bool
    {
        return $this->isModerationNotified;
    }

    public function setIsModerationNotified(bool $isModerationNotified): self
    {
        $this->isModerationNotified = $isModerationNotified;
        return $this;
    }

    public function getPdfDriveFileId(): ?string
{
    return $this->pdfDriveFileId;
}

public function setPdfDriveFileId(?string $pdfDriveFileId): static
{
    $this->pdfDriveFileId = $pdfDriveFileId;
    return $this;
}

public function getPdfDriveLink(): ?string
{
    return $this->pdfDriveLink;
}

public function setPdfDriveLink(?string $pdfDriveLink): static
{
    $this->pdfDriveLink = $pdfDriveLink;
    return $this;
}

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): static
    {
        // set the owning side of the relation if necessary
        if ($conversation->getPost() !== $this) {
            $conversation->setPost($this);
        }

        $this->conversation = $conversation;

        return $this;
    }

    public function isProfane(): bool
    {
        return $this->isProfane;
    }

    public function setIsProfane(bool $isProfane): static
    {
        $this->isProfane = $isProfane;
        return $this;
    }

    public function getProfaneWords(): int
    {
        return $this->profaneWords;
    }

    public function setProfaneWords(int $profaneWords): static
    {
        $this->profaneWords = $profaneWords;
        return $this;
    }
}
