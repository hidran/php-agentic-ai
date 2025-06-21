<?php
namespace App;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use OpenAI\Client;


class RagService {
    public function __construct(
        private readonly Client $openai,
        private readonly EmbeddingGeneratorInterface $embedder,
        private readonly QdrantRepository $repo,
        private string $collection = 'faqs',
        private readonly int $topK = 3
    ) {}

    public function answer(string $userQuery): array {
        $vec = $this->embedder->embedText($userQuery);

        $hits = $this->repo->search($vec, $this->topK);

        $firstHits = array_slice($hits, 0, $this->topK);
        $context = [];
        foreach($firstHits as $hit) {
            $context[] = $hit['payload']['question'].'=>'.$hit['payload']['answer'];;
        }
        $messages = [
            ['role'=>'system','content'=>'You are a helpful FAQ assistant. Use the following context: '.implode(' | ', $context)],
            ['role'=>'user','content'=>$userQuery]
        ];
        $resp = $this->openai->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages
        ]);
        return [
            'answer' => $resp['choices'][0]['message']['content'],
            'retrieved' => $context
        ];
    }
}
