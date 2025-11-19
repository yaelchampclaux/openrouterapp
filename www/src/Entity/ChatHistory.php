<?php
// src/Entity/ChatHistory.php

namespace App\Entity;

use App\Repository\ChatHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatHistoryRepository::class)]
#[ORM\Table(name: 'chat_history')]
#[ORM\HasLifecycleCallbacks]
class ChatHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $prompt = null;

    #[ORM\Column(length: 255)]
    private ?string $model = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $response = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fileMetadata = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): static
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(string $response): static
    {
        $this->response = $response;
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

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getFileMetadata(): ?string
    {
        return $this->fileMetadata;
    }

    public function setFileMetadata(?string $fileMetadata): static
    {
        $this->fileMetadata = $fileMetadata;
        return $this;
    }
}