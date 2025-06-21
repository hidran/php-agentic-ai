<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use App\QdrantRepository;
use App\RagService;


Dotenv::createImmutable(__DIR__ . '/..')->load();

$config = new OpenAIConfig();
$config->apiKey = $_ENV['OPENAI_API_KEY'];
if (!empty($_ENV['OPENAI_BASE_URL'])) {
    $config->url = $_ENV['OPENAI_BASE_URL'];
}

// Initialize embedding generator
$embedder = new OpenAI3SmallEmbeddingGenerator($config);


// Initialize OpenAI client
$openaiClient = OpenAI::factory()
    ->withApiKey($_ENV['OPENAI_API_KEY'])
    ->make();

// Initialize Qdrant vector store
$qdrant = new \Qdrant\Qdrant(
    (new \Qdrant\Http\Builder())->build(new \Qdrant\Config($_ENV['QDRANT_URL']))
);
$repo = new QdrantRepository($qdrant);

// Initialize RAG service
$rag = new RagService(
    $openaiClient,
   $embedder,
    $repo,
    'faqs',
    3
);

$q = trim($_GET['q'] ?? '');
if (!$q) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing query']);
    exit;
}

header('Content-Type: application/json');

try {
    echo json_encode($rag->answer($q));
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
