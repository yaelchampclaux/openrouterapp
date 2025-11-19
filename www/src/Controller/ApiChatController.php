<?php
// src/Controller/ApiChatController.php

namespace App\Controller;
  
use App\Entity\ChatHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class ApiChatController extends AbstractController
{
    private $client;
    private $entityManager;
    private $logger;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request, HttpClientInterface $client): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $prompt = $data['prompt'] ?? '';
            $model = $data['model'] ?? '';
    
            $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;
    
            if (!$apiKey) {
                return $this->json(['error' => 'Missing API key.'], 500);
            }
    
            $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);
    
            $result = $response->toArray(false);
            $responseText = $result['choices'][0]['message']['content'] ?? 'No response.';
    
            // Save to database
            $chatHistory = new ChatHistory();
            $chatHistory->setPrompt($prompt);
            $chatHistory->setModel($model);
            $chatHistory->setResponse($responseText);
            
            $this->entityManager->persist($chatHistory);
            $this->entityManager->flush();
    
            return $this->json([
                'response' => $responseText,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/models', name: 'api_models', methods: ['GET'])]
    public function getModels(HttpClientInterface $client): JsonResponse
    {
        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;

        if (!$apiKey) {
            return $this->json(['error' => 'Missing API key.'], 500);
        }

        $response = $client->request('GET', 'https://openrouter.ai/api/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $result = $response->toArray(false);

        $formattedModels = [];
        foreach ($result['data'] ?? [] as $model) {
            $isFree = (strpos($model['id'], ':free') !== false);
            
            // Extract price information
            $pricing = $model['pricing'] ?? null;
            $inputPrice = $pricing['prompt'] ?? $pricing['input'] ?? 0;
            $outputPrice = $pricing['completion'] ?? $pricing['output'] ?? 0;
            
            // Total price per 1 million tokens
            $totalPricePerMillion = ($inputPrice + $outputPrice) * 1000000; 

            // Create a more readable label
            $parts = explode('/', $model['id']);
            $modelName = end($parts);
            $provider = isset($parts[0]) ? $parts[0] : '';
            
            $modelName = str_replace(['-', '_'], ' ', $modelName);
            $modelName = ucwords($modelName);
            
            $label = $provider ? "$provider - $modelName" : $modelName;
            $label = str_replace(':free', '', $label);

            // Déterminer si le modèle peut traiter des fichiers
            $canProcessFiles = $this->modelCanProcessFiles($model);
            
            // Déterminer le type de support PDF
            $pdfSupport = 'none';
            if ($canProcessFiles) {
                // Claude models can handle PDFs directly
                if (strpos($model['id'], 'claude') !== false) {
                    $pdfSupport = 'native';
                }
                // Gemini models might need special handling
                else if (strpos($model['id'], 'gemini') !== false || strpos($model['id'], 'google') !== false) {
                    $pdfSupport = 'limited'; // They might not support PDFs directly
                }
                // Other vision models
                else if (strpos(strtolower($model['description'] ?? ''), 'vision') !== false) {
                    $pdfSupport = 'possible';
                }
            }

            $formattedModels[] = [
                'id' => $model['id'],
                'label' => $label,
                'description' => htmlspecialchars($model['description'] ?? ''),
                'is_free' => $isFree,
                'total_price' => $totalPricePerMillion,
                'canProcessFiles' => $canProcessFiles,
                'pdfSupport' => $pdfSupport,
                'capabilities' => $model['capabilities'] ?? []
            ];
        }

        // Sort models
        usort($formattedModels, function($a, $b) {
            if (!$a['is_free'] && $b['is_free']) {
                return -1;
            }
            if ($a['is_free'] && !$b['is_free']) {
                return 1;
            }
            return $b['total_price'] <=> $a['total_price'];
        });

        return $this->json(['models' => $formattedModels]);
    }

    private function modelCanProcessFiles(array $model): bool
    {
        // Liste mise à jour des mots-clés
        $fileProcessingKeywords = [
            'vision', 'image', 'multimodal', 'document', 'file', 'upload', 
            'process-image', 'process-document', 'image-understanding',
            'visual', 'picture', 'photo'
        ];

        // Exclure certains modèles qui ont ces mots-clés mais ne supportent pas vraiment les fichiers
        $excludedModels = [
            'gpt-3.5', 'text-davinci', 'text-curie', 'text-babbage', 'text-ada'
        ];

        $modelId = strtolower($model['id'] ?? '');
        
        // Vérifier si le modèle est dans la liste d'exclusion
        foreach ($excludedModels as $excluded) {
            if (strpos($modelId, $excluded) !== false) {
                return false;
            }
        }

        $searchString = strtolower(
            ($model['description'] ?? '') . ' ' . 
            $modelId . ' ' . 
            json_encode($model['capabilities'] ?? [])
        );

        // Vérifier les mots-clés positifs
        foreach ($fileProcessingKeywords as $keyword) {
            if (strpos($searchString, $keyword) !== false) {
                return true;
            }
        }

        // Vérifications spécifiques par fournisseur
        if (strpos($modelId, 'claude-3') !== false) {
            return true; // Tous les Claude 3 supportent les images/documents
        }
        
        if (strpos($modelId, 'gpt-4-vision') !== false || strpos($modelId, 'gpt-4-turbo') !== false) {
            return true;
        }
        
        if (strpos($modelId, 'gemini') !== false && strpos($modelId, 'pro') !== false) {
            return true; // Gemini Pro models support vision
        }

        return false;
    }
}