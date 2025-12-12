<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Conversation;
use App\Entity\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testMessageInitialization(): void
    {
        // Act
        $message = new Message();

        // Assert
        $this->assertNull($message->getId());
        $this->assertNull($message->getContent());
        $this->assertNull($message->getRole());
        $this->assertNull($message->getCreatedAt());
        $this->assertNull($message->getConversation());
    }

    public function testSetAndGetContent(): void
    {
        // Arrange
        $message = new Message();
        $content = 'Ceci est un message de test';

        // Act
        $result = $message->setContent($content);

        // Assert
        $this->assertSame($message, $result); // Test fluent interface
        $this->assertEquals($content, $message->getContent());
    }

    public function testSetAndGetRole(): void
    {
        // Arrange
        $message = new Message();

        // Act & Assert - User role
        $message->setRole('user');
        $this->assertEquals('user', $message->getRole());

        // Act & Assert - Assistant role
        $message->setRole('assistant');
        $this->assertEquals('assistant', $message->getRole());
    }

    public function testSetAndGetCreatedAt(): void
    {
        // Arrange
        $message = new Message();
        $date = new \DateTime('2024-06-15 10:30:00');

        // Act
        $result = $message->setCreatedAt($date);

        // Assert
        $this->assertSame($message, $result);
        $this->assertEquals($date, $message->getCreatedAt());
    }

    public function testSetAndGetConversation(): void
    {
        // Arrange
        $message = new Message();
        $conversation = new Conversation();
        $conversation->setModelId('test-model');
        $conversation->setTitle('Test conversation');

        // Act
        $result = $message->setConversation($conversation);

        // Assert
        $this->assertSame($message, $result);
        $this->assertSame($conversation, $message->getConversation());
    }

    public function testSetConversationToNull(): void
    {
        // Arrange
        $message = new Message();
        $conversation = new Conversation();
        $conversation->setModelId('test-model');
        $conversation->setTitle('Test');
        
        $message->setConversation($conversation);

        // Act
        $message->setConversation(null);

        // Assert
        $this->assertNull($message->getConversation());
    }

    public function testContentWithLongText(): void
    {
        // Arrange
        $message = new Message();
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 1000);

        // Act
        $message->setContent($longContent);

        // Assert
        $this->assertEquals($longContent, $message->getContent());
    }

    public function testContentWithSpecialCharacters(): void
    {
        // Arrange
        $message = new Message();
        $specialContent = "Test avec des caractères spéciaux: é à ü ñ 中文 🎉 <script>alert('test')</script>";

        // Act
        $message->setContent($specialContent);

        // Assert
        $this->assertEquals($specialContent, $message->getContent());
    }

    public function testContentWithMarkdown(): void
    {
        // Arrange
        $message = new Message();
        $markdownContent = "# Titre\n\n**Gras** et *italique*\n\n```php\necho 'code';\n```\n\n- Liste\n- Items";

        // Act
        $message->setContent($markdownContent);

        // Assert
        $this->assertEquals($markdownContent, $message->getContent());
    }

    public function testContentWithMultipleLines(): void
    {
        // Arrange
        $message = new Message();
        $multilineContent = "Ligne 1\nLigne 2\nLigne 3\n\nParagraphe 2";

        // Act
        $message->setContent($multilineContent);

        // Assert
        $this->assertEquals($multilineContent, $message->getContent());
    }

    public function testRoleCanBeAnyString(): void
    {
        // Arrange
        $message = new Message();

        // Note: L'entité n'a pas de validation, donc tout string est accepté
        // Dans un vrai projet, on ajouterait une validation

        // Act
        $message->setRole('system');

        // Assert
        $this->assertEquals('system', $message->getRole());
    }

    public function testFluentInterface(): void
    {
        // Arrange
        $message = new Message();
        $conversation = new Conversation();
        $conversation->setModelId('test');
        $conversation->setTitle('Test');

        // Act - Chaînage des méthodes
        $result = $message
            ->setContent('Test content')
            ->setRole('user')
            ->setCreatedAt(new \DateTime())
            ->setConversation($conversation);

        // Assert
        $this->assertSame($message, $result);
        $this->assertEquals('Test content', $message->getContent());
        $this->assertEquals('user', $message->getRole());
        $this->assertNotNull($message->getCreatedAt());
        $this->assertSame($conversation, $message->getConversation());
    }

    public function testMessageWithEmptyContent(): void
    {
        // Arrange
        $message = new Message();

        // Act
        $message->setContent('');

        // Assert
        $this->assertEquals('', $message->getContent());
    }

    public function testCreatedAtWithDifferentTimezones(): void
    {
        // Arrange
        $message = new Message();
        $dateUtc = new \DateTime('2024-06-15 12:00:00', new \DateTimeZone('UTC'));
        $dateParis = new \DateTime('2024-06-15 14:00:00', new \DateTimeZone('Europe/Paris'));

        // Act & Assert UTC
        $message->setCreatedAt($dateUtc);
        $this->assertEquals($dateUtc, $message->getCreatedAt());

        // Act & Assert Paris
        $message->setCreatedAt($dateParis);
        $this->assertEquals($dateParis, $message->getCreatedAt());
    }
}