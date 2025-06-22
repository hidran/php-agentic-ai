<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Qdrant\Config;
use Qdrant\Http\Builder;
use Qdrant\Qdrant;
use LLPhant\OpenAIConfig;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use App\{
    OpenAIEmbedder,
    QdrantRepository,
    FAQAgent,
    LoggingAgent,
    FallbackAgent,
    StaticAgent
};


// 1. Load environment variables
Dotenv::createImmutable(__DIR__ . '/..')->load();

// 2. Configure OpenAI embedding generator
$config = new OpenAIConfig();
$config->apiKey = $_ENV['OPENAI_API_KEY'];
if (!empty($_ENV['OPENAI_BASE_URL'])) {
    $config->url = $_ENV['OPENAI_BASE_URL'];
}

$generator = new OpenAI3SmallEmbeddingGenerator($config);
$embedder  = new OpenAIEmbedder($generator); // implements EmbedderInterface

// 3. Initialize Qdrant repository
$qdrant = new Qdrant(
    (new Builder())
        ->build(new Config($_ENV['QDRANT_URL']))
);
$repo = new QdrantRepository($qdrant);

// 4. Build the agent pipeline
$openaiClient  = OpenAI::client($_ENV['OPENAI_API_KEY']);

$faqAgent      = new FAQAgent($openaiClient, $embedder, $repo, 3);
$loggingAgent  = new LoggingAgent($faqAgent);
$fallbackAgent = new FallbackAgent(
    $loggingAgent,
    new StaticAgent("I'm , I don't know that answer."),
    0.6
);

// 5. HTTP request handling
$q = trim($_GET['q'] ?? '');
if (!$q) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing query']);
    exit;
}

header('Content-Type: application/json');

try {
    $answer = $fallbackAgent->handle($q);
    echo json_encode(['answer' => $answer]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
