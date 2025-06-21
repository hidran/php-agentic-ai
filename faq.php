<?php
declare(strict_types=1);
//Suppose you have this FAQ
$faq = [
    [
        'id' => 1,
        'question' => 'How do I reset my password?',
        'answer'   => 'Click “Forgot Password” on the login page and follow the instructions.',
    ],
    [
        'id' => 2,
        'question' => 'Where can I download my invoice?',
        'answer'   => 'Go to the Billing section and click “Download Invoice”.',
    ],
    // ... more entries
];

require __DIR__ . '/vendor/autoload.php';


use Dotenv\Dotenv;
use LLPhant\Embeddings\OpenAIEmbeddingGenerator;


// Load environment variables from .env
Dotenv::createImmutable(__DIR__)->load();

// Instantiate the OpenAI client (v0.13.0+)

$openaiKey = getenv('OPENAI_API_KEY');
$embedder = new OpenAIEmbeddingGenerator($openaiKey);
$client = OpenAI::client($openaiKey);
foreach ($faq as &$entry) {
    $entry['embedding'] = $embedder->generateEmbedding($entry['question']);
}
unset($entry); // best practice for reference
// Get the user’s question from the query string
$userQuestion = trim($_GET['q'] ?? '');


if ($userQuestion === '') {
    echo json_encode(['error' => 'No question provided.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$userEmbedding = $embedder->generateEmbedding($userQuestion);
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
