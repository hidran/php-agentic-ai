<?php
namespace App;

interface AgentInterface
{
    /**
     * Handle an input string and return an output string.
     *
     * @param string $input  User’s request or context
     * @return string        Agent’s response
     */
    public function handle(string $input): string;
}
