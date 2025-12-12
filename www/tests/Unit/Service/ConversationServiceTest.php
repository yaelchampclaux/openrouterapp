<?php

namespace App\Tests\Unit\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Service\ConversationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ConversationServiceTest extends TestCase
{
    private ConversationService $conversationService;
    private MockObject&EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->conversationService = new ConversationService($this->entityManager);
    }

    public function testCreateConversationWithDefaultTitle(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-sonnet';
        $initialPrompt = 'Bonjour, comment vas-tu ?';

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $conversation = $this->conversationService->createConversation($modelId, $initialPrompt);

        // Assert
        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals($modelId, $conversation->getModelId());
        $this->assertEquals($initialPrompt, $conversation->getTitle());
        $this->assertCount(1, $conversation->getMessages());
        
        $firstMessage = $conversation->getMessages()->first();
        $this->assertEquals('user', $firstMessage->getRole());
        $this->assertEquals($initialPrompt, $firstMessage->getContent());
    }

    public function testCreateConversationWithCustomTitle(): void
    {
        // Arrange
        $modelId = 'openai/gpt-4';
        $initialPrompt = 'Explique-moi la relativité générale';
        $customTitle = 'Discussion physique';

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $conversation = $this->conversationService->createConversation($modelId, $initialPrompt, $customTitle);

        // Assert
        $this->assertEquals($customTitle, $conversation->getTitle());
    }

    public function testCreateConversationTruncatesLongTitle(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-opus';
        $longPrompt = str_repeat('a', 100); // 100 caractères

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $conversation = $this->conversationService->createConversation($modelId, $longPrompt);

        // Assert
        $expectedTitle = str_repeat('a', 50) . '...';
        $this->assertEquals($expectedTitle, $conversation->getTitle());
        $this->assertEquals(53, mb_strlen($conversation->getTitle()));
    }

    public function testCreateConversationWithExactly50Chars(): void
    {
        // Arrange
        $modelId = 'anthropic/claude-3-haiku';
        $exactPrompt = str_repeat('b', 50); // Exactement 50 caractères

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $conversation = $this->conversationService->createConversation($modelId, $exactPrompt);

        // Assert
        $this->assertEquals($exactPrompt, $conversation->getTitle());
        $this->assertStringEndsNotWith('...', $conversation->getTitle());
    }

    public function testContinueConversation(): void
    {
        // Arrange
        $conversation = new Conversation();
        $conversation->setModelId('anthropic/claude-3-sonnet');
        $conversation->setTitle('Test conversation');

        $newPrompt = 'Peux-tu préciser ?';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Message $message) use ($newPrompt, $conversation) {
                return $message->getContent() === $newPrompt
                    && $message->getRole() === 'user'
                    && $message->getConversation() === $conversation;
            }));
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $message = $this->conversationService->continueConversation($conversation, $newPrompt);

        // Assert
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($newPrompt, $message->getContent());
        $this->assertEquals('user', $message->getRole());
        $this->assertInstanceOf(\DateTime::class, $message->getCreatedAt());
    }

    public function testGetConversationContextReturnsOrderedMessages(): void
    {
        // Arrange
        $conversation = new Conversation();
        $conversation->setModelId('test-model');
        $conversation->setTitle('Test');

        // Créer des messages dans le désordre
        $message1 = new Message();
        $message1->setContent('Premier message');
        $message1->setRole('user');
        $message1->setCreatedAt(new \DateTime('2024-01-01 10:00:00'));
        $message1->setConversation($conversation);

        $message2 = new Message();
        $message2->setContent('Réponse assistant');
        $message2->setRole('assistant');
        $message2->setCreatedAt(new \DateTime('2024-01-01 10:01:00'));
        $message2->setConversation($conversation);

        $message3 = new Message();
        $message3->setContent('Deuxième message');
        $message3->setRole('user');
        $message3->setCreatedAt(new \DateTime('2024-01-01 10:02:00'));
        $message3->setConversation($conversation);

        // Ajouter dans le désordre
        $conversation->addMessage($message3);
        $conversation->addMessage($message1);
        $conversation->addMessage($message2);

        // Act
        $context = $this->conversationService->getConversationContext($conversation);

        // Assert
        $this->assertCount(3, $context);
        
        // Vérifier l'ordre chronologique
        $this->assertEquals('Premier message', $context[0]['content']);
        $this->assertEquals('user', $context[0]['role']);
        
        $this->assertEquals('Réponse assistant', $context[1]['content']);
        $this->assertEquals('assistant', $context[1]['role']);
        
        $this->assertEquals('Deuxième message', $context[2]['content']);
        $this->assertEquals('user', $context[2]['role']);
    }

    public function testGetConversationContextWithEmptyConversation(): void
    {
        // Arrange
        $conversation = new Conversation();
        $conversation->setModelId('test-model');
        $conversation->setTitle('Empty conversation');

        // Act
        $context = $this->conversationService->getConversationContext($conversation);

        // Assert
        $this->assertIsArray($context);
        $this->assertEmpty($context);
    }

    public function testGetConversationContextReturnsCorrectFormat(): void
    {
        // Arrange
        $conversation = new Conversation();
        $conversation->setModelId('test-model');
        $conversation->setTitle('Test');

        $message = new Message();
        $message->setContent('Test content');
        $message->setRole('user');
        $message->setCreatedAt(new \DateTime());
        $message->setConversation($conversation);
        
        $conversation->addMessage($message);

        // Act
        $context = $this->conversationService->getConversationContext($conversation);

        // Assert
        $this->assertArrayHasKey('role', $context[0]);
        $this->assertArrayHasKey('content', $context[0]);
        $this->assertCount(2, $context[0]); // Seulement role et content
    }
}