<?php
namespace App;

readonly class FallbackAgent implements AgentInterface
{
    public function __construct(
        private AgentInterface $primary,
        private AgentInterface $backup,
        private float $confidenceThreshold = 0.5
    ) {}

    public function handle(string $input): string
    {
        $primaryOutput = $this->primary->handle($input);

        // Basic heuristic: if primary’s answer is generic or the confidence is low
        if (str_contains(strtolower($primaryOutput), 'sorry')
            || ($this->estimateConfidence($primaryOutput) < $this->confidenceThreshold)) {
            return $this->backup->handle($input);
        }

        return $primaryOutput;
    }

    private function estimateConfidence(string $text): float
    {
        // In production, derive from LLM token log-probs or metadata
        return random_int(0, 100) / 100;
    }
}
