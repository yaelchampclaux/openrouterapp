<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Summary;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class SummaryTest extends TestCase
{
    public function testSummaryInitialization(): void
    {
        // Arrange
        $before = new \DateTimeImmutable();

        // Act
        $summary = new Summary();
        
        $after = new \DateTimeImmutable();

        // Assert
        $this->assertNull($summary->getId());
        $this->assertNull($summary->getChatHistoryId());
        $this->assertNull($summary->getSummaryText());
        $this->assertNull($summary->getTokensCount());
        $this->assertNull($summary->getType());
        
        // Vérifier que createdAt et updatedAt sont initialisés
        $this->assertInstanceOf(\DateTimeImmutable::class, $summary->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $summary->getUpdatedAt());
        
        // Vérifier que les dates sont dans la plage attendue
        $this->assertGreaterThanOrEqual($before, $summary->getCreatedAt());
        $this->assertLessThanOrEqual($after, $summary->getCreatedAt());
    }

    public function testSetAndGetChatHistoryId(): void
    {
        // Arrange
        $summary = new Summary();
        $chatHistoryId = 123;

        // Act
        $result = $summary->setChatHistoryId($chatHistoryId);

        // Assert
        $this->assertSame($summary, $result); // Test fluent interface
        $this->assertEquals($chatHistoryId, $summary->getChatHistoryId());
    }

    public function testSetAndGetSummaryText(): void
    {
        // Arrange
        $summary = new Summary();
        $text = 'Ceci est un résumé de la conversation.';

        // Act
        $result = $summary->setSummaryText($text);

        // Assert
        $this->assertSame($summary, $result);
        $this->assertEquals($text, $summary->getSummaryText());
    }

    public function testSetAndGetTokensCount(): void
    {
        // Arrange
        $summary = new Summary();
        $tokensCount = 1500;

        // Act
        $result = $summary->setTokensCount($tokensCount);

        // Assert
        $this->assertSame($summary, $result);
        $this->assertEquals($tokensCount, $summary->getTokensCount());
    }

    public function testSetTokensCountToNull(): void
    {
        // Arrange
        $summary = new Summary();
        $summary->setTokensCount(1000);

        // Act
        $summary->setTokensCount(null);

        // Assert
        $this->assertNull($summary->getTokensCount());
    }

    public function testSetAndGetType(): void
    {
        // Arrange
        $summary = new Summary();
        $type = 'automatic';

        // Act
        $result = $summary->setType($type);

        // Assert
        $this->assertSame($summary, $result);
        $this->assertEquals($type, $summary->getType());
    }

    public function testSetTypeToNull(): void
    {
        // Arrange
        $summary = new Summary();
        $summary->setType('manual');

        // Act
        $summary->setType(null);

        // Assert
        $this->assertNull($summary->getType());
    }

    public function testSetUpdatedAtValueLifecycleCallback(): void
    {
        // Arrange
        $summary = new Summary();
        $originalUpdatedAt = $summary->getUpdatedAt();
        
        // Attendre un peu pour avoir une différence de temps
        usleep(1000); // 1ms

        // Act
        $summary->setUpdatedAtValue();

        // Assert
        $this->assertInstanceOf(\DateTimeImmutable::class, $summary->getUpdatedAt());
        $this->assertNotEquals($originalUpdatedAt, $summary->getUpdatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $summary->getUpdatedAt());
    }

    public function testSummaryTextWithLongContent(): void
    {
        // Arrange
        $summary = new Summary();
        $longText = str_repeat('Ceci est un texte de résumé très long. ', 500);

        // Act
        $summary->setSummaryText($longText);

        // Assert
        $this->assertEquals($longText, $summary->getSummaryText());
    }

    public function testSummaryTextWithSpecialCharacters(): void
    {
        // Arrange
        $summary = new Summary();
        $specialText = "Résumé avec caractères spéciaux: é à ü ñ 中文 🎉 \"guillemets\" et 'apostrophes'";

        // Act
        $summary->setSummaryText($specialText);

        // Assert
        $this->assertEquals($specialText, $summary->getSummaryText());
    }

    public function testSummaryTextWithMarkdown(): void
    {
        // Arrange
        $summary = new Summary();
        $markdownText = "# Résumé\n\n**Points clés:**\n- Point 1\n- Point 2\n\n*Conclusion*";

        // Act
        $summary->setSummaryText($markdownText);

        // Assert
        $this->assertEquals($markdownText, $summary->getSummaryText());
    }

    public function testTokensCountWithZero(): void
    {
        // Arrange
        $summary = new Summary();

        // Act
        $summary->setTokensCount(0);

        // Assert
        $this->assertEquals(0, $summary->getTokensCount());
    }

    public function testTokensCountWithLargeValue(): void
    {
        // Arrange
        $summary = new Summary();
        $largeTokenCount = 1000000; // 1 million de tokens

        // Act
        $summary->setTokensCount($largeTokenCount);

        // Assert
        $this->assertEquals($largeTokenCount, $summary->getTokensCount());
    }

    public function testTypeWithDifferentValues(): void
    {
        // Arrange
        $summary = new Summary();
        $types = ['automatic', 'manual', 'scheduled', 'on-demand'];

        foreach ($types as $type) {
            // Act
            $summary->setType($type);

            // Assert
            $this->assertEquals($type, $summary->getType());
        }
    }

    public function testFluentInterface(): void
    {
        // Arrange & Act
        $summary = (new Summary())
            ->setChatHistoryId(1)
            ->setSummaryText('Test summary')
            ->setTokensCount(500)
            ->setType('automatic');

        // Assert
        $this->assertEquals(1, $summary->getChatHistoryId());
        $this->assertEquals('Test summary', $summary->getSummaryText());
        $this->assertEquals(500, $summary->getTokensCount());
        $this->assertEquals('automatic', $summary->getType());
    }

    public function testCreatedAtIsImmutable(): void
    {
        // Arrange
        $summary = new Summary();
        $createdAt = $summary->getCreatedAt();

        // Assert
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
    }

    public function testUpdatedAtIsImmutable(): void
    {
        // Arrange
        $summary = new Summary();
        $updatedAt = $summary->getUpdatedAt();

        // Assert
        $this->assertInstanceOf(\DateTimeImmutable::class, $updatedAt);
    }
}