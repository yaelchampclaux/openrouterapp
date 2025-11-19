<?php
// src/Service/PdfTextExtractor.php

namespace App\Service;

use Smalot\PdfParser\Parser;
use Psr\Log\LoggerInterface;

class PdfTextExtractor
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function extractTextFromBase64(string $base64Data): string
    {
        try {
            // Décoder le base64
            $pdfContent = base64_decode($base64Data);
            
            if ($pdfContent === false) {
                throw new \Exception('Invalid base64 data');
            }

            // Parser le PDF
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfContent);
            
            // Extraire le texte
            $text = $pdf->getText();
            
            // Nettoyer le texte
            $text = trim($text);
            $text = preg_replace('/\s+/', ' ', $text); // Remplacer les espaces multiples
            
            $this->logger->info('PDF text extracted', [
                'text_length' => strlen($text),
                'preview' => substr($text, 0, 200) . '...'
            ]);
            
            return $text;
            
        } catch (\Exception $e) {
            $this->logger->error('PDF extraction error', [
                'error' => $e->getMessage()
            ]);
            
            throw new \RuntimeException('Failed to extract text from PDF: ' . $e->getMessage());
        }
    }
}