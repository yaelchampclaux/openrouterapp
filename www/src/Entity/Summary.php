<?php
// src/Entity/Summary.php

namespace App\Entity;

use App\Repository\SummaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SummaryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Summary
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $chatHistoryId = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $summaryText = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokensCount = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getChatHistoryId(): ?int
    {
        return $this->chatHistoryId;
    }

    public function setChatHistoryId(int $chatHistoryId): static
    {
        $this->chatHistoryId = $chatHistoryId;

        return $this;
    }

    public function getSummaryText(): ?string
    {
        return $this->summaryText;
    }

    public function setSummaryText(string $summaryText): static
    {
        $this->summaryText = $summaryText;

        return $this;
    }

    public function getTokensCount(): ?int
    {
        return $this->tokensCount;
    }

    public function setTokensCount(?int $tokensCount): static
    {
        $this->tokensCount = $tokensCount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}