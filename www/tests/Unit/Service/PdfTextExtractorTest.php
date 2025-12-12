<?php

namespace App\Tests\Unit\Service;

use App\Service\PdfTextExtractor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PdfTextExtractorTest extends TestCase
{
    private PdfTextExtractor $pdfTextExtractor;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pdfTextExtractor = new PdfTextExtractor($this->logger);
    }

    public function testExtractTextFromBase64ThrowsExceptionOnInvalidBase64(): void
    {
        // Arrange
        $invalidBase64 = '!!!invalid-base64-data!!!';

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'PDF extraction error',
                $this->callback(function (array $context) {
                    return isset($context['error']);
                })
            );

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to extract text from PDF/');

        // Act
        $this->pdfTextExtractor->extractTextFromBase64($invalidBase64);
    }

    public function testExtractTextFromBase64ThrowsExceptionOnInvalidPdfContent(): void
    {
        // Arrange
        // Base64 valide mais contenu non-PDF
        $notAPdf = base64_encode('This is not a PDF file content');

        $this->logger->expects($this->once())
            ->method('error');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to extract text from PDF/');

        // Act
        $this->pdfTextExtractor->extractTextFromBase64($notAPdf);
    }

    public function testExtractTextFromBase64LogsSuccessfulExtraction(): void
    {
        // Arrange
        // Créer un PDF minimal valide en base64
        // Note: Ce test nécessite un vrai PDF pour fonctionner correctement
        // Dans un environnement réel, on utiliserait un fichier de fixture
        
        // Pour ce test, on va simplement vérifier que le logger est appelé
        // quand l'extraction réussit (en mockant ou avec un vrai PDF)
        
        // Si on veut un test plus complet, il faut créer un PDF de test
        $this->markTestSkipped(
            'Ce test nécessite un fichier PDF de fixture pour être exécuté correctement.'
        );
    }

    /**
     * Test avec un PDF réel (à activer si un fichier de fixture est disponible)
     */
    public function testExtractTextFromValidPdf(): void
    {
        // Arrange
        $pdfFixturePath = __DIR__ . '/../../Fixtures/sample.pdf';
        
        if (!file_exists($pdfFixturePath)) {
            $this->markTestSkipped(
                'Le fichier de fixture PDF n\'existe pas. Créez tests/Fixtures/sample.pdf pour activer ce test.'
            );
        }

        $pdfContent = file_get_contents($pdfFixturePath);
        $base64Pdf = base64_encode($pdfContent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'PDF text extracted',
                $this->callback(function (array $context) {
                    return isset($context['text_length']) 
                        && isset($context['preview'])
                        && $context['text_length'] > 0;
                })
            );

        // Act
        $result = $this->pdfTextExtractor->extractTextFromBase64($base64Pdf);

        // Assert
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testExtractTextFromBase64TrimsWhitespace(): void
    {
        // Ce test vérifie que le texte extrait est bien nettoyé
        // Il nécessite un PDF de test avec des espaces multiples
        
        $this->markTestSkipped(
            'Ce test nécessite un fichier PDF de fixture spécifique.'
        );
    }

    public function testExtractTextFromBase64ReplacesMultipleSpaces(): void
    {
        // Ce test vérifie que les espaces multiples sont remplacés par un seul
        // Il nécessite un PDF de test avec des espaces multiples
        
        $this->markTestSkipped(
            'Ce test nécessite un fichier PDF de fixture spécifique.'
        );
    }
}