<?php
namespace App;

readonly class LoggingAgent implements AgentInterface
{
    public function __construct(
        private AgentInterface $delegate,
        private string         $logFile = __DIR__ . '/../../logs/faq_agent.log'
    ) {}

    public function handle(string $input): string
    {
        $output = $this->delegate->handle($input);

        // Ensure the directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        file_put_contents(
            $this->logFile,
            date('c') . " Q: $input\nA: $output\n\n",
            FILE_APPEND | LOCK_EX
        );

        return $output;
    }
}
