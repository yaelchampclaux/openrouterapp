<?php
// src/Controller/ChatHistoryController.php

namespace App\Controller;

use App\Repository\ChatHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ChatHistory; 

class ChatHistoryController extends AbstractController
{
    #[Route('/chat/history', name: 'chat_history')]
    public function index(ChatHistoryRepository $chatHistoryRepository): Response
    {
        $historyEntities = $chatHistoryRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Create a normalized array with all needed data
        $history = [];
        foreach ($historyEntities as $entity) {
            $history[] = [
                'id' => $entity->getId(),
                'prompt' => $entity->getPrompt(),
                'response' => $entity->getResponse(),
                'model' => $entity->getModel(),
                'title' => $entity->getTitle(),
                'createdAt' => $entity->getCreatedAt()->format('c') // ISO 8601 format
            ];
        }

        return $this->render('chat/history.html.twig', [
            'history' => $history
        ]);
    }
}