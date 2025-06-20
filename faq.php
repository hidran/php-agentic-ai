<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';


use Dotenv\Dotenv;


// Load environment variables from .env
Dotenv::createImmutable(__DIR__)->load();

// Instantiate the OpenAI client (v0.13.0+)
$client = OpenAI::client($_ENV['OPENAI_API_KEY']);

// Get the user’s question from the query string
$question = trim($_GET['q'] ?? '');

if ($question === '') {
    echo json_encode(['error' => 'No question provided.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Perform a chat completion as an FAQ bot
$response = $client->chat()->create([
    'model'    => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You answer succinctly as an FAQ bot.'],
        ['role' => 'user',   'content' => $question],
    ],
]);

// Return the answer as JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'answer' => $response['choices'][0]['message']['content'] ?? 'No response from API.',
], JSON_UNESCAPED_UNICODE);
