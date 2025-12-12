<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Conversation;
use App\Entity\Message;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class ConversationTest extends TestCase
{
    public function testConversationInitialization(): void
    {
        // Act
        $conversation = new Conversation();

        // Assert
        $this->assertNull($conversation->getId());
        $this->assertInstanceOf(Collection::class, $conversation->getMessages());
        $this->assertCount(0, $conversation->getMessages());
        $this->assertInstanceOf(\DateTime::class, $conversation->getCreatedAt());
    }

    public function testSetAndGetModelId(): void
    {
        // Arrange
        $conversation = new Conversation();
        $modelId = 'anthropic/claude-3-sonnet';

        // Act
        $result = $conversation->setModelId($modelId);

        // Assert
        $this->assertSame($conversation, $result); // Test fluent interface
        $this->assertEquals($modelId, $conversation->getModelId());
    }

    public function testSetAndGetTitle(): void
    {
        // Arrange
        $conversation = new Conversation();
        $title = 'Ma conversation de test';

        // Act
        $result = $conversation->setTitle($title);

        // Assert
        $this->assertSame($conversation, $result);
        $this->assertEquals($title, $conversation->getTitle());
    }

    public function testSetAndGetCreatedAt(): void
    {
        // Arrange
        $conversation = new Conversation();
        $date = new \DateTime('2024-06-15 14:30:00');

        // Act
        $result = $conversation->setCreatedAt($date);

        // Assert
        $this->assertSame($conversation, $result);
        $this->assertEquals($date, $conversation->getCreatedAt());
    }

    public function testAddMessage(): void
    {
        // Arrange
        $conversation = new Conversation();
        $message = new Message();
        $message->setContent('Test message');
        $message->setRole('user');
        $message->setCreatedAt(new \DateTime());

        // Act
        $result = $conversation->addMessage($message);

        // Assert
        $this->assertSame($conversation, $result);
        $this->assertCount(1, $conversation->getMessages());
        $this->assertTrue($conversation->getMessages()->contains($message));
        $this->assertSame($conversation, $message->getConversation());
    }

    public function testAddMessageDoesNotAddDuplicate(): void
    {
        // Arrange
        $conversation = new Conversation();
        $message = new Message();
        $message->setContent('Test message');
        $message->setRole('user');
        $message->setCreatedAt(new \DateTime());

        // Act
        $conversation->addMessage($message);
        $conversation->addMessage($message); // Ajout en double

        // Assert
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testAddMultipleMessages(): void
    {
        // Arrange
        $conversation = new Conversation();
        
        $message1 = new Message();
        $message1->setContent('Premier message');
        $message1->setRole('user');
        $message1->setCreatedAt(new \DateTime());

        $message2 = new Message();
        $message2->setContent('Deuxième message');
        $message2->setRole('assistant');
        $message2->setCreatedAt(new \DateTime());

        $message3 = new Message();
        $message3->setContent('Troisième message');
        $message3->setRole('user');
        $message3->setCreatedAt(new \DateTime());

        // Act
        $conversation->addMessage($message1);
        $conversation->addMessage($message2);
        $conversation->addMessage($message3);

        // Assert
        $this->assertCount(3, $conversation->getMessages());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        // Arrange
        $before = new \DateTime();
        
        // Act
        $conversation = new Conversation();
        
        $after = new \DateTime();

        // Assert
        $createdAt = $conversation->getCreatedAt();
        $this->assertInstanceOf(\DateTime::class, $createdAt);
        $this->assertGreaterThanOrEqual($before, $createdAt);
        $this->assertLessThanOrEqual($after, $createdAt);
    }

    public function testTitleCanBeEmpty(): void
    {
        // Arrange
        $conversation = new Conversation();

        // Act
        $conversation->setTitle('');

        // Assert
        $this->assertEquals('', $conversation->getTitle());
    }

    public function testTitleWithSpecialCharacters(): void
    {
        // Arrange
        $conversation = new Conversation();
        $titleWithSpecialChars = "Test <script>alert('xss')</script> & émojis 🎉";

        // Act
        $conversation->setTitle($titleWithSpecialChars);

        // Assert
        $this->assertEquals($titleWithSpecialChars, $conversation->getTitle());
    }

    public function testModelIdWithDifferentFormats(): void
    {
        // Arrange
        $conversation = new Conversation();
        
        $modelIds = [
            'anthropic/claude-3-opus',
            'openai/gpt-4-turbo',
            'google/gemini-pro',
            'meta-llama/llama-3-70b-instruct',
            'mistralai/mistral-large'
        ];

        foreach ($modelIds as $modelId) {
            // Act
            $conversation->setModelId($modelId);

            // Assert
            $this->assertEquals($modelId, $conversation->getModelId());
        }
    }
}