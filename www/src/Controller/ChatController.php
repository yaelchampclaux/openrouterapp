<?php 

// src/Controller/ChatController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Service\ConversationService;
use App\Service\OpenRouterService;
use App\Service\PdfTextExtractor; // AJOUT DE L'IMPORT MANQUANT
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ChatHistory; 
use Psr\Log\LoggerInterface;  

class ChatController extends AbstractController
{
    private PdfTextExtractor $pdfTextExtractor;
    private ConversationService $conversationService;
    private OpenRouterService $openRouterService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        PdfTextExtractor $pdfTextExtractor,
        ConversationService $conversationService,
        OpenRouterService $openRouterService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->pdfTextExtractor = $pdfTextExtractor;
        $this->conversationService = $conversationService;
        $this->openRouterService = $openRouterService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/', name: 'spa_chat')]
    public function chatPage(): Response
    {
        return $this->render('chat/index.html.twig');
    }

    #[Route('/chat/start', name: 'chat_start', methods: ['POST'])]
    public function startConversation(Request $request): Response {
        try {
            $data = json_decode($request->getContent(), true);

            // Log the incoming request
            $this->logger->info('Chat Start Request', [
                'has_file' => isset($data['file']),
                'prompt_length' => strlen($data['prompt'] ?? ''),
                'model' => $data['model'] ?? 'not specified'
            ]);

            $modelId = $data['model'] ?? '';
            $initialPrompt = $data['prompt'] ?? '';
            $title = $data['title'] ?? null;
            $fileData = $data['file'] ?? null;

            // Process PDF if uploaded
            if ($fileData && $fileData['type'] === 'application/pdf') {
                try {
                    $extractedText = $this->pdfTextExtractor->extractTextFromBase64($fileData['base64']);
                    
                    // Append extracted text to the prompt
                    $initialPrompt = "[PDF Content: " . $fileData['name'] . "]\n\n" 
                        . $extractedText . "\n\n"
                        . "---End of PDF content---\n\n" 
                        . $initialPrompt;
                    
                    $this->logger->info('PDF processed', [
                        'extracted_length' => strlen($extractedText)
                    ]);
                    
                    // Remove the file data to not send it to OpenRouter
                    $fileData = null;
                } catch (\Exception $e) {
                    $this->logger->error('PDF extraction failed', [
                        'error' => $e->getMessage()
                    ]);
                    
                    return $this->json([
                        'error' => 'Failed to process PDF: ' . $e->getMessage()
                    ], 400);
                }
            }

            // File validation for images
            if ($fileData) {
                if (empty($fileData['base64'])) {
                    throw new \Exception("File base64 data is empty");
                }

                // Base64 validation
                if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $fileData['base64'])) {
                    throw new \Exception("Invalid base64 data");
                }

                // File size check (base64 is ~33% larger than original)
                $estimatedSize = strlen($fileData['base64']) * 0.75;
                $maxFileSize = 10 * 1024 * 1024; // 10 MB
                if ($estimatedSize > $maxFileSize) {
                    throw new \Exception("File is too large. Maximum size is 10MB.");
                }

                // File type validation (only images now, PDFs are processed above)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileData['type'], $allowedTypes)) {
                    throw new \Exception("Unsupported file type: " . $fileData['type']);
                }

                $this->logger->info('File validation passed', [
                    'type' => $fileData['type'],
                    'name' => $fileData['name'] ?? 'unknown',
                    'estimated_size' => $estimatedSize
                ]);
            }

            // Create a new conversation
            $conversation = $this->conversationService->createConversation($modelId, $initialPrompt, $title);

            // Prepare messages for OpenRouter
            $messagesForOpenRouter = [
                ['role' => 'user', 'content' => $initialPrompt]
            ];

            // Prepare options for file upload (only for images now)
            $options = [];
            if ($fileData) {
                $options['file'] = $fileData;
            }

            // Send to OpenRouter
            $responseFromOpenRouter = $this->openRouterService->sendMessage($modelId, $messagesForOpenRouter, $options);
            $responseText = $responseFromOpenRouter['content'];

            // Store in chat_history
            $chatHistory = new ChatHistory();
            $chatHistory->setPrompt($initialPrompt);
            $chatHistory->setModel($modelId);
            $chatHistory->setResponse($responseText);
            $chatHistory->setTitle($conversation->getTitle());

            // If file was uploaded, store additional metadata
            if (isset($data['file'])) {
                $chatHistory->setFileMetadata(json_encode([
                    'name' => $data['file']['name'],
                    'type' => $data['file']['type']
                ]));
            }

            $this->entityManager->persist($chatHistory);
            
            // Save assistant's response as a message
            $assistantMessage = new Message();
            $assistantMessage->setContent($responseText);
            $assistantMessage->setRole('assistant');
            $assistantMessage->setConversation($conversation);
            $assistantMessage->setCreatedAt(new \DateTime());

            $this->entityManager->persist($assistantMessage);
            $this->entityManager->flush();

            return $this->json([
                'conversationId' => $conversation->getId(),
                'conversationTitle' => $conversation->getTitle(),
                'response' => $responseText
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Chat Start Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/chat/continue/{conversationId}', name: 'chat_continue', methods: ['POST'])]
    public function continueConversation(int $conversationId, Request $request): Response {
        try {
            $data = json_decode($request->getContent(), true);
            $newPrompt = $data['prompt'] ?? '';
            $fileData = $data['file'] ?? null;

            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return $this->json(['error' => 'Conversation not found'], 404);
            }

            // Process PDF if uploaded
            if ($fileData && $fileData['type'] === 'application/pdf') {
                try {
                    $extractedText = $this->pdfTextExtractor->extractTextFromBase64($fileData['base64']);
                    
                    // Append extracted text to the prompt
                    $newPrompt = "[PDF Content: " . $fileData['name'] . "]\n\n" 
                        . $extractedText . "\n\n"
                        . "---End of PDF content---\n\n" 
                        . $newPrompt;
                    
                    $this->logger->info('PDF processed in continue', [
                        'extracted_length' => strlen($extractedText)
                    ]);
                    
                    // Remove the file data to not send it to OpenRouter
                    $fileData = null;
                } catch (\Exception $e) {
                    $this->logger->error('PDF extraction failed in continue', [
                        'error' => $e->getMessage()
                    ]);
                    
                    return $this->json([
                        'error' => 'Failed to process PDF: ' . $e->getMessage()
                    ], 400);
                }
            }

            // File validation for images
            if ($fileData) {
                if (empty($fileData['base64'])) {
                    throw new \Exception("File base64 data is empty");
                }

                if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $fileData['base64'])) {
                    throw new \Exception("Invalid base64 data");
                }

                $estimatedSize = strlen($fileData['base64']) * 0.75;
                $maxFileSize = 10 * 1024 * 1024;
                if ($estimatedSize > $maxFileSize) {
                    throw new \Exception("File is too large. Maximum size is 10MB.");
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileData['type'], $allowedTypes)) {
                    throw new \Exception("Unsupported file type: " . $fileData['type']);
                }
            }
            
            // Add user's new message to conversation
            $userMessage = $this->conversationService->continueConversation($conversation, $newPrompt);

            // Get full conversation context
            $context = $this->conversationService->getConversationContext($conversation);

            // Prepare options for file upload (only for images)
            $options = [];
            if ($fileData) {
                $options['file'] = $fileData;
            }

            // Send to OpenRouter with full context
            $responseFromOpenRouter = $this->openRouterService->sendMessage(
                $conversation->getModelId(), 
                $context, 
                $options
            );
            $responseText = $responseFromOpenRouter['content'];

            // Store in chat_history
            $chatHistory = new ChatHistory();
            $chatHistory->setPrompt($newPrompt);
            $chatHistory->setModel($conversation->getModelId());
            $chatHistory->setResponse($responseText);
            $chatHistory->setTitle($conversation->getTitle());

            // If file was uploaded, store additional metadata
            if (isset($data['file'])) {
                $chatHistory->setFileMetadata(json_encode([
                    'name' => $data['file']['name'],
                    'type' => $data['file']['type']
                ]));
            }

            $this->entityManager->persist($chatHistory);

            // Save assistant's response as a message
            $assistantMessage = new Message();
            $assistantMessage->setContent($responseText);
            $assistantMessage->setRole('assistant');
            $assistantMessage->setConversation($conversation);
            $assistantMessage->setCreatedAt(new \DateTime());

            $this->entityManager->persist($assistantMessage);
            $this->entityManager->flush();

            return $this->json([
                'conversationId' => $conversation->getId(),
                'response' => $responseText
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Chat Continue Error', [
                'message' => $e->getMessage(),
                'conversation_id' => $conversationId
            ]);
            
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}