<?php
namespace App;

use OpenAI\Client as OpenAIClient;

readonly class FAQAgent implements AgentInterface
{
    public function __construct(
        private OpenAIClient $llm,
        private EmbedderInterface $embedder,
        private QdrantRepository $repo,
        private int $topK = 3
    ) {}

    public function handle(string $question): string
    {
        // 1. Embed and retrieve top-K FAQs
        $vector = $this->embedder->embed($question);
        $hits   = $this->repo->search($vector, $this->topK);

        // 2. Build context block
        $context = array_map(static fn($hit) =>
        "Q: {$hit['payload']['question']}\nA: {$hit['payload']['answer']}",
            $hits
        );

        $prompt = "Use the following FAQ context to answer:\n\n" . implode("\n---\n", $context);

        // 3. Generate answer via LLM
        $response = $this->llm->chat()->create([
            'model'    => 'gpt-3.5-turbo',
            'messages' => [
                ['role'=>'system','content'=>$prompt],
                ['role'=>'user','content'=>$question],
            ],
        ]);

        return $response['choices'][0]['message']['content'];
    }
}
