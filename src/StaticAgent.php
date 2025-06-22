<?php
// File: src/StaticAgent.php
namespace App;

class StaticAgent implements AgentInterface
{
    private string $message;

    /**
     * @param string $message  The constant reply this agent will return.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $input  The user’s question (ignored).
     * @return string        Always returns the predefined message.
     */
    public function handle(string $input): string
    {
        return $this->message;
    }
}
