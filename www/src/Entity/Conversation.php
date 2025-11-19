<?php 
// src/Entity/Conversation.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'conversations')]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    private $title;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation')]
    private $messages;

    #[ORM\Column(type: 'string')]
    private $modelId;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // Ajoutez les getters et setters nécessaires
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModelId(): string
    {
        return $this->modelId;
    }

    public function setModelId(string $modelId): self
    {
        $this->modelId = $modelId;
        return $this;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setConversation($this);
        }
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
   
}