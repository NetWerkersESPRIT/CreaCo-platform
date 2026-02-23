<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $senderUser = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isRead = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(options: ["default" => false])]
    private bool $isProfane = false;

    #[ORM\Column(options: ["default" => 0])]
    private int $profaneWords = 0;

    #[ORM\Column(options: ["default" => 0])]
    private int $grammarErrors = 0;

    #[ORM\Column(length: 50, options: ["default" => "visible"])]
    private string $status = "visible";

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isRead = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getSenderUser(): ?Users
    {
        return $this->senderUser;
    }

    public function setSenderUser(?Users $senderUser): static
    {
        $this->senderUser = $senderUser;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

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

    public function getGrammarErrors(): int
    {
        return $this->grammarErrors;
    }

    public function setGrammarErrors(int $grammarErrors): static
    {
        $this->grammarErrors = $grammarErrors;
        return $this;
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
}
