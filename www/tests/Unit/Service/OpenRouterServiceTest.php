<?php

namespace App\Tests\Unit\Service;

use App\Service\OpenRouterService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OpenRouterServiceTest extends TestCase
{
    private OpenRouterService $openRouterService;
    private MockObject&HttpClientInterface $httpClient;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // S'assurer que la variable d'environnement est définie
        $_ENV['OPENROUTER_API_KEY'] = 'test_api_key';
        $_ENV['APP_URL'] = 'https://test.localhost';
        
        $this->openRouterService = new OpenRouterService($this->httpClient, $this->logger);
    }

    protected function tearDown(): void
    {
        unset($_ENV['OPENROUTER_API_KEY']);
        unset($_ENV['APP_URL']);
    }

    public function testSendMessageSuccessfully(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Bonjour !']
        ];

        $expectedResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Bonjour ! Comment puis-je vous aider ?'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($expectedResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                $this->callback(function (array $options) use ($modelId) {
                    return $options['json']['model'] === $modelId
                        && isset($options['headers']['Authorization'])
                        && $options['headers']['Authorization'] === 'Bearer test_api_key';
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->openRouterService->sendMessage($modelId, $messages);

        // Assert
        $this->assertEquals('assistant', $result['role']);
        $this->assertEquals('Bonjour ! Comment puis-je vous aider ?', $result['content']);
    }

    public function testSendMessageWithMultipleMessages(): void
    {
        // Arrange
        $modelId = 'openai/gpt-4';
        $messages = [
            ['role' => 'user', 'content' => 'Bonjour'],
            ['role' => 'assistant', 'content' => 'Bonjour !'],
            ['role' => 'user', 'content' => 'Comment ça va ?']
        ];

        $expectedResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Je vais bien, merci !'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($expectedResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                $this->callback(function (array $options) {
                    return count($options['json']['messages']) === 3;
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->openRouterService->sendMessage($modelId, $messages);

        // Assert
        $this->assertEquals('Je vais bien, merci !', $result['content']);
    }

    public function testSendMessageWithImageFile(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Décris cette image']
        ];
        $options = [
            'file' => [
                'type' => 'image/jpeg',
                'base64' => 'base64encodedimagedata'
            ]
        ];

        $expectedResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Cette image montre...'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($expectedResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                $this->callback(function (array $options) {
                    $lastMessage = end($options['json']['messages']);
                    return is_array($lastMessage['content'])
                        && $lastMessage['content'][0]['type'] === 'text'
                        && $lastMessage['content'][1]['type'] === 'image_url';
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->openRouterService->sendMessage($modelId, $messages, $options);

        // Assert
        $this->assertEquals('Cette image montre...', $result['content']);
    }

    public function testSendMessageWithPdfForClaude(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Résume ce document']
        ];
        $options = [
            'file' => [
                'type' => 'application/pdf',
                'base64' => 'base64encodedpdfdata'
            ]
        ];

        $expectedResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Ce document traite de...'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($expectedResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                $this->callback(function (array $options) {
                    $lastMessage = end($options['json']['messages']);
                    return is_array($lastMessage['content'])
                        && $lastMessage['content'][1]['type'] === 'document'
                        && $lastMessage['content'][1]['source']['media_type'] === 'application/pdf';
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->openRouterService->sendMessage($modelId, $messages, $options);

        // Assert
        $this->assertEquals('Ce document traite de...', $result['content']);
    }

    public function testSendMessageWithPdfForGemini(): void
    {
        // Arrange
        $modelId = 'google/gemini-pro-vision';
        $messages = [
            ['role' => 'user', 'content' => 'Analyse ce PDF']
        ];
        $options = [
            'file' => [
                'type' => 'application/pdf',
                'base64' => 'base64encodedpdfdata'
            ]
        ];

        $expectedResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Analyse du PDF...'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($expectedResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                $this->callback(function (array $options) {
                    $lastMessage = end($options['json']['messages']);
                    return is_array($lastMessage['content'])
                        && $lastMessage['content'][1]['type'] === 'file_data'
                        && $lastMessage['content'][1]['file_data']['mime_type'] === 'application/pdf';
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->openRouterService->sendMessage($modelId, $messages, $options);

        // Assert
        $this->assertEquals('Analyse du PDF...', $result['content']);
    }

    public function testSendMessageThrowsExceptionOnEmptyBase64(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];
        $options = [
            'file' => [
                'type' => 'image/png',
                'base64' => '' // Empty base64
            ]
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base64 file data is empty');

        // Act
        $this->openRouterService->sendMessage($modelId, $messages, $options);
    }

    public function testSendMessageThrowsExceptionOnUnsupportedFileType(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];
        $options = [
            'file' => [
                'type' => 'application/zip',
                'base64' => 'somebase64data'
            ]
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file type: application/zip');

        // Act
        $this->openRouterService->sendMessage($modelId, $messages, $options);
    }

    public function testSendMessageThrowsExceptionOnApiError(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];

        $errorResponse = [
            'error' => [
                'message' => 'Rate limit exceeded'
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(429);
        $response->method('getContent')->willReturn(json_encode($errorResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenRouter API Error: Rate limit exceeded');

        // Act
        $this->openRouterService->sendMessage($modelId, $messages);
    }

    public function testSendMessageThrowsExceptionOnInvalidJson(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn('invalid json {{{');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON response from OpenRouter');

        // Act
        $this->openRouterService->sendMessage($modelId, $messages);
    }

    public function testSendMessageThrowsExceptionOnEmptyChoices(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode(['choices' => []]));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No valid response from OpenRouter');

        // Act
        $this->openRouterService->sendMessage($modelId, $messages);
    }

    public function testSendMessageThrowsExceptionOnErrorInResponse(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];

        $errorResponse = [
            'error' => [
                'message' => 'Model not found'
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($errorResponse));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenRouter API Error: Model not found');

        // Act
        $this->openRouterService->sendMessage($modelId, $messages);
    }

    public function testSendMessageLogsRequestAndResponse(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $messages = [
            ['role' => 'user', 'content' => 'Test logging']
        ];

        $expectedResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Response'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($expectedResponse));

        $this->httpClient->method('request')->willReturn($response);

        // On s'attend à 2 appels de log info (request et response)
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message) {
                $this->assertContains($message, ['OpenRouter Request', 'OpenRouter Response']);
            });

        // Act
        $this->openRouterService->sendMessage($modelId, $messages);
    }
}