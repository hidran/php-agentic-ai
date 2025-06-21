<?php
namespace App;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\OpenAIEmbeddingGenerator;

class OpenAIEmbedder implements EmbeddingGeneratorInterface {
    public function __construct(private readonly OpenAIEmbeddingGenerator $g) {}


    public function embedText(string $text): array
    {
        return $this->g->generateEmbedding($text);
    }

    public function embedDocument(Document $document): Document
    {
        // TODO: Implement embedDocument() method.
    }

    public function embedDocuments(array $documents): array
    {
        // TODO: Implement embedDocuments() method.
    }

    public function getEmbeddingLength(): int
    {
        // TODO: Implement getEmbeddingLength() method.
    }
}
