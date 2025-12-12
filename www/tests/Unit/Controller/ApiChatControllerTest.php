<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ApiChatController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests unitaires pour les méthodes privées du ApiChatController
 * 
 * Note: Ces tests utilisent la réflexion pour tester les méthodes privées.
 * Dans un projet réel, on pourrait extraire cette logique dans un service dédié.
 */
class ApiChatControllerTest extends TestCase
{
    /**
     * Helper pour accéder aux méthodes privées
     */
    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Helper pour créer un mock du controller avec les dépendances nécessaires
     */
    private function createControllerMock(): ApiChatController
    {
        $httpClient = $this->createMock(\Symfony\Contracts\HttpClient\HttpClientInterface::class);
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        
        return new ApiChatController($httpClient, $entityManager, $logger);
    }

    public function testModelCanProcessFilesWithVisionKeyword(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'openai/gpt-4-vision-preview',
            'description' => 'A model with vision capabilities'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithImageKeyword(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'some-model',
            'description' => 'This model can process image inputs'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithMultimodalKeyword(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'some-multimodal-model',
            'description' => 'A multimodal model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithClaude3(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'anthropic/claude-3-sonnet',
            'description' => 'Claude 3 Sonnet model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithGpt4Turbo(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'openai/gpt-4-turbo',
            'description' => 'GPT-4 Turbo model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithGeminiPro(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'google/gemini-pro-vision',
            'description' => 'Gemini Pro Vision model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesReturnsFalseForGpt35(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'openai/gpt-3.5-turbo',
            'description' => 'GPT-3.5 Turbo model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertFalse($result);
    }

    public function testModelCanProcessFilesReturnsFalseForTextDavinci(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'openai/text-davinci-003',
            'description' => 'Text Davinci model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertFalse($result);
    }

    public function testModelCanProcessFilesReturnsFalseForBasicModel(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'mistralai/mistral-7b',
            'description' => 'A basic text model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertFalse($result);
    }

    public function testModelCanProcessFilesWithCapabilities(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'some-model',
            'description' => 'A model',
            'capabilities' => ['vision', 'text']
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithDocumentKeyword(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'some-model',
            'description' => 'Can process document inputs'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithVisualKeyword(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'some-model',
            'description' => 'Visual understanding model'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesCaseInsensitive(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'some-model',
            'description' => 'A VISION model with IMAGE capabilities'
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertTrue($result);
    }

    public function testModelCanProcessFilesWithEmptyDescription(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'basic-model',
            'description' => ''
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertFalse($result);
    }

    public function testModelCanProcessFilesWithNullDescription(): void
    {
        // Arrange
        $controller = $this->createControllerMock();
        $model = [
            'id' => 'basic-model'
            // pas de description
        ];

        // Act
        $result = $this->invokePrivateMethod($controller, 'modelCanProcessFiles', [$model]);

        // Assert
        $this->assertFalse($result);
    }
}