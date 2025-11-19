<?php

// src/Service/OpenRouterService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class OpenRouterService
{
    private $client;
    private $apiKey;
    private $logger;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->apiKey = $_ENV['OPENROUTER_API_KEY'];
        $this->logger = $logger; 
    }

    public function sendMessage(string $modelId, array $messages, array $options = []): array
    {
        try {
            $requestData = [
                'model' => $modelId,
                'messages' => []
            ];

            // Gestion spéciale pour les fichiers
            if (isset($options['file'])) {
                $fileData = $options['file'];
                
                // Validation du fichier
                if (empty($fileData['base64'])) {
                    throw new \InvalidArgumentException("Base64 file data is empty");
                }

                // Pour les modèles qui supportent les fichiers, on doit structurer différemment
                // Récupérer le dernier message utilisateur
                $lastUserMessage = null;
                $lastUserMessageIndex = -1;
                for ($i = count($messages) - 1; $i >= 0; $i--) {
                    if ($messages[$i]['role'] === 'user') {
                        $lastUserMessage = $messages[$i];
                        $lastUserMessageIndex = $i;
                        break;
                    }
                }

                // Ajouter tous les messages sauf le dernier utilisateur
                for ($i = 0; $i < count($messages); $i++) {
                    if ($i !== $lastUserMessageIndex) {
                        $requestData['messages'][] = [
                            'role' => $messages[$i]['role'],
                            'content' => $messages[$i]['content']
                        ];
                    }
                }

                // Construire le message avec le fichier
                $messageContent = [];

                // D'abord le texte du prompt
                if ($lastUserMessage) {
                    $messageContent[] = [
                        'type' => 'text',
                        'text' => $lastUserMessage['content']
                    ];
                }

                // Ensuite le fichier selon son type
                if ($fileData['type'] === 'application/pdf') {
                    // Pour les PDF, essayer plusieurs formats selon le modèle
                    if (strpos($modelId, 'claude') !== false) {
                        // Format Claude
                        $messageContent[] = [
                            'type' => 'document',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'application/pdf',
                                'data' => $fileData['base64']
                            ]
                        ];
                    } else if (strpos($modelId, 'gemini') !== false || strpos($modelId, 'google') !== false) {
                        // Format Gemini - ils semblent préférer un format différent
                        $messageContent[] = [
                            'type' => 'file_data',
                            'file_data' => [
                                'mime_type' => 'application/pdf',
                                'data' => $fileData['base64']
                            ]
                        ];
                    } else {
                        // Format générique
                        $messageContent[] = [
                            'type' => 'document',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'application/pdf',
                                'data' => $fileData['base64']
                            ]
                        ];
                    }
                } else if (in_array($fileData['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
                    // Pour les images
                    $messageContent[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:' . $fileData['type'] . ';base64,' . $fileData['base64']
                        ]
                    ];
                } else {
                    throw new \InvalidArgumentException("Unsupported file type: " . $fileData['type']);
                }

                // Ajouter le message avec le contenu structuré
                $requestData['messages'][] = [
                    'role' => 'user',
                    'content' => $messageContent
                ];

            } else {
                // Pas de fichier, traitement normal
                foreach ($messages as $message) {
                    $requestData['messages'][] = [
                        'role' => $message['role'] ?? 'user',
                        'content' => $message['content']
                    ];
                }
            }

            // Log de débogage
            $this->logger->info('OpenRouter Request', [
                'model' => $modelId,
                'has_file' => isset($options['file']),
                'file_type' => $options['file']['type'] ?? 'none',
                'messages_structure' => json_encode($requestData['messages'], JSON_PRETTY_PRINT)
            ]);

            // Requête HTTP
            $response = $this->client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $_ENV['APP_URL'] ?? 'https://localhost',
                    'X-Title' => 'OpenRouter Chat App'
                ],
                'json' => $requestData,
                'timeout' => 120
            ]);

            $statusCode = $response->getStatusCode();
            $responseContent = $response->getContent(false);
            
            // Log de la réponse
            $this->logger->info('OpenRouter Response', [
                'status_code' => $statusCode,
                'response_preview' => substr($responseContent, 0, 500)
            ]);

            if ($statusCode !== 200) {
                $this->logger->error('OpenRouter API Error Response', [
                    'status' => $statusCode,
                    'response' => $responseContent,
                    'model' => $modelId,
                    'file_type' => $options['file']['type'] ?? 'none'
                ]);
                
                // Parser l'erreur si possible
                $errorData = json_decode($responseContent, true);
                if ($errorData && isset($errorData['error'])) {
                    $errorMessage = $errorData['error']['message'] ?? $errorData['error'];
                    throw new \RuntimeException('OpenRouter API Error: ' . $errorMessage);
                }
                
                throw new \RuntimeException('OpenRouter API Error: ' . $responseContent);
            }

            $result = json_decode($responseContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from OpenRouter');
            }

            if (isset($result['error'])) {
                $this->logger->error('OpenRouter Error in Response', [
                    'error' => $result['error'],
                    'model' => $modelId
                ]);
                throw new \RuntimeException('OpenRouter API Error: ' . ($result['error']['message'] ?? json_encode($result['error'])));
            }

            if (!isset($result['choices']) || empty($result['choices'])) {
                $this->logger->error('No choices in response', [
                    'response' => $result,
                    'model' => $modelId
                ]);
                throw new \RuntimeException('No valid response from OpenRouter');
            }
            
            return $result['choices'][0]['message'];
            
        } catch (\Exception $e) {
            $this->logger->error('OpenRouterService Exception', [
                'message' => $e->getMessage(),
                'model' => $modelId,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}