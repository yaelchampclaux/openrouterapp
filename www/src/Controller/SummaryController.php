<?php
// src/Controller/SummaryController.php

namespace App\Controller;

use App\Entity\ChatHistory;
use App\Entity\Summary;
use App\Service\OpenRouterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SummaryController extends AbstractController
{
    private $entityManager;
    private $openRouterService;

    public function __construct(EntityManagerInterface $entityManager, OpenRouterService $openRouterService)
    {
        $this->entityManager = $entityManager;
        $this->openRouterService = $openRouterService;
    }

    #[Route('/api/chat-history/{id}/resume-info', name: 'api_chat_history_resume_info', methods: ['GET'])]
    public function getChatHistoryResumeInfo(int $id): JsonResponse
    {
        // Récupérer l'entrée ChatHistory
        $chatHistory = $this->entityManager->getRepository(ChatHistory::class)->find($id);
        
        if (!$chatHistory) {
            return $this->json(['error' => 'Chat history entry not found'], 404);
        }
        
        // Récupérer le modèle pour avoir les prix
        $modelId = $chatHistory->getModel();
        $modelPricing = $this->getModelPricing($modelId);
        
        // Récupérer TOUS les messages de la conversation jusqu'à celui-ci
        $title = $chatHistory->getTitle();
        $thread = $this->entityManager->getRepository(ChatHistory::class)
            ->createQueryBuilder('ch')
            ->where('ch.title = :title')
            ->setParameter('title', $title)
            ->orderBy('ch.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Construire le contexte complet jusqu'au message sélectionné
        $fullContext = "";
        $messageCount = 0;
        $foundCurrentMessage = false;
        
        foreach ($thread as $message) {
            // On ajoute le message au contexte
            $fullContext .= "USER: " . $message->getPrompt() . "\n\n";
            $fullContext .= "ASSISTANT: " . $message->getResponse() . "\n\n";
            $messageCount++;
            
            // Si on a atteint le message actuel, on arrête
            if ($message->getId() === $chatHistory->getId()) {
                $foundCurrentMessage = true;
                break;
            }
        }
        
        // Si on n'a pas trouvé le message actuel, il y a un problème
        if (!$foundCurrentMessage) {
            return $this->json(['error' => 'Current message not found in thread'], 500);
        }
        
        // Calculer les tokens pour le contexte complet
        $completeTokenCount = $this->estimateTokenCount($fullContext);
        $completeCost = $this->calculateCost($completeTokenCount, $modelPricing);
        
        // Log pour debug
        error_log("Full context length: " . strlen($fullContext));
        error_log("Message count included: " . $messageCount);
        error_log("Complete token count: " . $completeTokenCount);
        error_log("Complete cost: " . $completeCost);
        
        // Données de base
        $result = [
            'id' => $chatHistory->getId(),
            'title' => $chatHistory->getTitle(),
            'model' => $chatHistory->getModel(),
            'messageCount' => $messageCount,
            'contextLength' => strlen($fullContext),
            'tokenEstimates' => [
                'complete' => $completeTokenCount,
                'summary' => null
            ],
            'costs' => [
                'complete' => $completeCost,
                'summary' => null
            ],
            'hasSummary' => false
        ];
        
        // Vérifier s'il existe un résumé pour cette entrée
        $summary = $this->entityManager->getRepository(Summary::class)->findOneBy([
            'chatHistoryId' => $chatHistory->getId()
        ]);
        
        if ($summary) {
            $summaryTokenCount = $summary->getTokensCount();
            $summaryCost = $this->calculateCost($summaryTokenCount, $modelPricing);
            
            $result['hasSummary'] = true;
            $result['summaryId'] = $summary->getId(); 
            $result['tokenEstimates']['summary'] = $summaryTokenCount;
            $result['costs']['summary'] = $summaryCost;
        } else {
            // Estimer le coût du résumé (environ 20% du contexte complet pour un résumé concis)
            $estimatedSummaryTokens = max(100, intval($completeTokenCount * 0.2)); // Au moins 100 tokens
            $estimatedSummaryCost = $this->calculateCost($estimatedSummaryTokens, $modelPricing);
            
            $result['tokenEstimates']['summary'] = $estimatedSummaryTokens;
            $result['costs']['summary'] = $estimatedSummaryCost;
            $result['summaryEstimated'] = true;
        }
        
        return $this->json($result);
    }
    
    #[Route('/api/chat-history/{id}/create-summary', name: 'api_create_chat_history_summary', methods: ['POST'])]
    public function createChatHistorySummary(int $id, Request $request): JsonResponse
    {
        // Récupérer l'entrée ChatHistory
        $chatHistory = $this->entityManager->getRepository(ChatHistory::class)->find($id);
        
        if (!$chatHistory) {
            return $this->json(['error' => 'Chat history entry not found'], 404);
        }
        
        // Vérifier si un résumé existe déjà
        $existingSummary = $this->entityManager->getRepository(Summary::class)->findOneBy([
            'chatHistoryId' => $chatHistory->getId()
        ]);
        
        if ($existingSummary) {
            $modelPricing = $this->getModelPricing($chatHistory->getModel());
            $cost = $this->calculateCost($existingSummary->getTokensCount(), $modelPricing);
            
            return $this->json([
                'success' => true,
                'summary' => $existingSummary->getSummaryText(),
                'tokensCount' => $existingSummary->getTokensCount(),
                'cost' => $cost,
                'id' => $existingSummary->getId()
            ]);
        }
        
        // Récupérer le titre
        $title = $chatHistory->getTitle();
        
        // Récupérer TOUS les messages jusqu'à celui-ci
        $thread = $this->entityManager->getRepository(ChatHistory::class)
            ->createQueryBuilder('ch')
            ->where('ch.title = :title')
            ->setParameter('title', $title)
            ->orderBy('ch.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Construction du contexte complet
        $fullConversation = "";
        $messageCount = 0;
        
        foreach ($thread as $message) {
            $fullConversation .= "USER: " . $message->getPrompt() . "\n\n";
            $fullConversation .= "ASSISTANT: " . $message->getResponse() . "\n\n";
            $messageCount++;
            
            // Si on a atteint le message actuel, on arrête
            if ($message->getId() === $chatHistory->getId()) {
                break;
            }
        }
        
        // Log pour debug
        error_log("Creating summary for conversation with " . $messageCount . " messages");
        error_log("Full conversation length: " . strlen($fullConversation));
        
        // Prompt pour un résumé concis
        $systemPrompt = "You are an expert AI summarization assistant. Create a CONCISE summary that captures the essential information needed to continue this conversation.

The conversation title is: \"{$title}\".

This conversation contains {$messageCount} messages.

Focus on:
- Key topics and decisions made
- Important technical details, names, and specifications
- Current state of the discussion
- Any unresolved questions

Be concise but complete. Aim for 150-250 words maximum.
Use clear, structured paragraphs.";
        
        // Préparer les messages pour l'IA
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => "Summarize this conversation:\n\n" . $fullConversation
            ]
        ];
        
        // Générer le résumé
        try {
            $response = $this->openRouterService->sendMessage($chatHistory->getModel(), $messages, ['temperature' => 0.3]);
            $summaryText = $response['content'];
            
            // Estimer le nombre de tokens
            $tokensCount = $this->estimateTokenCount($summaryText);
            
            // Créer l'entité Summary
            $summary = new Summary();
            $summary->setChatHistoryId($chatHistory->getId());
            $summary->setSummaryText($summaryText);
            $summary->setTokensCount($tokensCount);
            $summary->setType('intelligent');
            
            $this->entityManager->persist($summary);
            $this->entityManager->flush();
            
            // Calculer le coût
            $modelPricing = $this->getModelPricing($chatHistory->getModel());
            $cost = $this->calculateCost($tokensCount, $modelPricing);
            
            error_log("Summary created - Tokens: " . $tokensCount . ", Cost: " . $cost);
            
            return $this->json([
                'success' => true,
                'summary' => $summaryText,
                'tokensCount' => $tokensCount,
                'cost' => $cost,
                'id' => $summary->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/api/chat-history/{id}/summary', name: 'api_get_chat_history_summary', methods: ['GET'])]
    public function getChatHistorySummary(int $id): JsonResponse
    {
        $summary = $this->entityManager->getRepository(Summary::class)->findOneBy([
            'chatHistoryId' => $id
        ]);
        
        if (!$summary) {
            return $this->json(['error' => 'Summary not found'], 404);
        }
        
        // Récupérer le modèle pour calculer le coût
        $chatHistory = $this->entityManager->getRepository(ChatHistory::class)->find($id);
        $modelPricing = $this->getModelPricing($chatHistory->getModel());
        $cost = $this->calculateCost($summary->getTokensCount(), $modelPricing);
        
        return $this->json([
            'id' => $summary->getId(),
            'summary' => $summary->getSummaryText(),
            'tokensCount' => $summary->getTokensCount(),
            'cost' => $cost,
            'type' => $summary->getType()
        ]);
    }
    
    /**
     * Simple estimation of token count - a better implementation would use a proper tokenizer
     */
    private function estimateTokenCount(string $text): int
    {
        // Estimation plus précise : 
        // - Environ 4 caractères par token en anglais
        // - Ou environ 0.75 tokens par mot
        $charCount = strlen($text);
        $wordCount = str_word_count(strip_tags($text));
        
        // Utiliser la méthode qui donne le plus de tokens (plus conservateur)
        $charBasedEstimate = intval($charCount / 4);
        $wordBasedEstimate = intval($wordCount * 0.75);
        
        return max($charBasedEstimate, $wordBasedEstimate, 1); // Au moins 1 token
    }
    
    /**
     * Get model pricing information
     */
    private function getModelPricing(string $modelId): array
    {
        // Prix par défaut si on ne trouve pas le modèle
        $defaultPricing = [
            'input' => 0.000001,  // $0.001 per 1000 tokens = $0.000001 per token
            'output' => 0.000002  // $0.002 per 1000 tokens = $0.000002 per token
        ];
        
        // Récupérer les modèles depuis l'API OpenRouter
        try {
            $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;
            if (!$apiKey) {
                error_log("No OpenRouter API key found, using default pricing");
                return $defaultPricing;
            }
            
            $client = \Symfony\Component\HttpClient\HttpClient::create();
            $response = $client->request('GET', 'https://openrouter.ai/api/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);
            
            $result = $response->toArray(false);
            
            foreach ($result['data'] ?? [] as $model) {
                if ($model['id'] === $modelId) {
                    $pricing = $model['pricing'] ?? null;
                    if ($pricing) {
                        // Les prix sont donnés par million de tokens, on les convertit par token
                        $inputPrice = floatval($pricing['prompt'] ?? $pricing['input'] ?? 0);
                        $outputPrice = floatval($pricing['completion'] ?? $pricing['output'] ?? 0);
                        
                        error_log("Found model pricing for {$modelId}: input={$inputPrice}/M, output={$outputPrice}/M");
                        
                        return [
                            'input' => $inputPrice / 1000000,  // Convertir en prix par token
                            'output' => $outputPrice / 1000000
                        ];
                    }
                }
            }
            
            error_log("Model {$modelId} not found in pricing data, using default");
        } catch (\Exception $e) {
            error_log("Error fetching model pricing: " . $e->getMessage());
        }
        
        return $defaultPricing;
    }
    
    /**
     * Calculate cost based on token count and pricing
     */
    private function calculateCost(int $tokenCount, array $pricing): float
    {
        // On considère que la moitié des tokens sont input et l'autre moitié output
        // C'est une approximation car on ne connaît pas la répartition exacte
        $inputTokens = $tokenCount / 2;
        $outputTokens = $tokenCount / 2;
        
        $inputCost = $inputTokens * $pricing['input'];
        $outputCost = $outputTokens * $pricing['output'];
        
        $totalCost = $inputCost + $outputCost;
        
        error_log("Cost calculation: {$tokenCount} tokens, input cost={$inputCost}, output cost={$outputCost}, total={$totalCost}");
        
        return max(0.0001, $totalCost); // Au moins $0.0001
    }

    #[Route('/api/chat-history/{id}', name: 'api_get_chat_history', methods: ['GET'])]
    public function getChatHistory(int $id): JsonResponse
    {
        $chatHistory = $this->entityManager->getRepository(ChatHistory::class)->find($id);
        
        if (!$chatHistory) {
            return $this->json(['error' => 'Chat history not found'], 404);
        }
        
        return $this->json([
            'id' => $chatHistory->getId(),
            'prompt' => $chatHistory->getPrompt(),
            'response' => $chatHistory->getResponse(),
            'model' => $chatHistory->getModel(),
            'title' => $chatHistory->getTitle(),
            'createdAt' => $chatHistory->getCreatedAt()->format('c')
        ]);
    }

    #[Route('/api/summaries', name: 'api_list_summaries', methods: ['GET'])]
    public function listSummaries(): JsonResponse
    {
        $summaries = $this->entityManager->getRepository(Summary::class)->findAll();
        $result = [];
        
        foreach ($summaries as $summary) {
            $result[] = [
                'id' => $summary->getId(),
                'chatHistoryId' => $summary->getChatHistoryId(),
                'type' => $summary->getType(),
                'tokensCount' => $summary->getTokensCount(),
                'createdAt' => $summary->getCreatedAt()->format('c'),
                'summaryTextLength' => strlen($summary->getSummaryText())
            ];
        }
        
        return $this->json([
            'count' => count($result),
            'summaries' => $result
        ]);
    }

    #[Route('/api/chat-history/{id}/conversation-thread', name: 'api_get_conversation_thread', methods: ['GET'])]
    public function getConversationThread(int $id): JsonResponse
    {
        // Récupérer l'entrée ChatHistory actuelle
        $chatHistory = $this->entityManager->getRepository(ChatHistory::class)->find($id);
        
        if (!$chatHistory) {
            return $this->json(['error' => 'Chat history entry not found'], 404);
        }
        
        // Récupérer le titre pour la recherche
        $title = $chatHistory->getTitle();
        
        // Récupérer toutes les entrées avec le même titre, triées par date de création
        $thread = $this->entityManager->getRepository(ChatHistory::class)
            ->createQueryBuilder('ch')
            ->where('ch.title = :title')
            ->setParameter('title', $title)
            ->orderBy('ch.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Préparer le résultat
        $result = [
            'title' => $title,
            'model' => $chatHistory->getModel(),
            'currentMessageIndex' => -1,
            'messages' => []
        ];
        
        foreach ($thread as $index => $message) {
            $result['messages'][] = [
                'id' => $message->getId(),
                'prompt' => $message->getPrompt(),
                'response' => $message->getResponse(),
                'createdAt' => $message->getCreatedAt()->format('c')
            ];
            
            // Marquer l'index du message actuel
            if ($message->getId() === $chatHistory->getId()) {
                $result['currentMessageIndex'] = $index;
            }
        }
        
        return $this->json($result);
    }
}