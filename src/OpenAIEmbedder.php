<?php
namespace App;

use App\EmbedderInterface;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;

/**
 * OpenAIEmbedder wraps the LLPhant OpenAI3SmallEmbeddingGenerator
 * and implements the application EmbedderInterface for RAG.
 */
class OpenAIEmbedder implements EmbedderInterface
{
    private OpenAI3SmallEmbeddingGenerator $generator;

    /**
     * @param OpenAI3SmallEmbeddingGenerator $generator    LLPhant embedding generator
     */
    public function __construct(OpenAI3SmallEmbeddingGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Embed the given text into a semantic vector.
     *
     * @param string $text    The text to embed
     * @return float[]        Embedding vector
     */
    public function embed(string $text): array
    {
        return $this->generator->embedText($text);
    }

    /**
     * Get the dimensionality of the embedding vector.
     *
     * @return int
     */
    public function getEmbeddingLength(): int
    {
        return $this->generator->getEmbeddingLength();
    }
}
