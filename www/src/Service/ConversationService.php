<?php 

// src/Service/ConversationService.php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conversation;
use App\Entity\Message;
use Doctrine\Common\Collections\ArrayCollection;

class ConversationService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createConversation(string $modelId, string $initialPrompt, ?string $title = null): Conversation
    {
        $conversation = new Conversation();
        $conversation->setModelId($modelId);
        // Utilise le titre fourni, sinon génère un titre par défaut
        $conversation->setTitle($title ?: $this->generateTitle($initialPrompt));
         
        // Créer le premier message utilisateur
        $initialUserMessage = new Message();
        $initialUserMessage->setContent($initialPrompt);
        $initialUserMessage->setRole('user');
        $initialUserMessage->setConversation($conversation);
        $initialUserMessage->setCreatedAt(new \DateTime());

        $conversation->addMessage($initialUserMessage);

        $this->entityManager->persist($conversation);
        $this->entityManager->persist($initialUserMessage);
        $this->entityManager->flush();

        return $conversation;
    }

    public function continueConversation(Conversation $conversation, string $newPrompt): Message
    {
        $newMessage = new Message();
        $newMessage->setContent($newPrompt);
        $newMessage->setRole('user');
        $newMessage->setConversation($conversation);
        $newMessage->setCreatedAt(new \DateTime());

        $this->entityManager->persist($newMessage);
        $this->entityManager->flush();

        return $newMessage;
    }

    public function getConversationContext(Conversation $conversation): array
    {
        // Récupérer les messages triés par date de création pour maintenir l'ordre chronologique du contexte
        $messages = $conversation->getMessages()->toArray();
        usort($messages, function($a, $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });

        return array_map(function($message) {
            return [
                'role' => $message->getRole(),
                'content' => $message->getContent()
            ];
        }, $messages);
    }

    private function generateTitle(string $initialPrompt): string
    {
        // Logique pour générer un titre court basé sur le prompt initial
        // Limite à 50 caractères et ajoute "..." si plus long
        $title = mb_substr($initialPrompt, 0, 50);
        if (mb_strlen($initialPrompt) > 50) {
            $title .= '...';
        }
        return $title;
    }
}