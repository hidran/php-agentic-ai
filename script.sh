# -- PHP Interfaces and Classes --
cat > src/EmbedderInterface.php <<'EOF'
<?php
namespace App;
interface EmbedderInterface {
    public function embed(string $text): array;
}
EOF

cat > src/OpenAIEmbedder.php <<'EOF'
<?php
namespace App;
use LLPhant\Embeddings\OpenAIEmbeddingGenerator;

class OpenAIEmbedder implements EmbedderInterface {
    public function __construct(private OpenAIEmbeddingGenerator $g) {}
    public function embed(string $text): array {
        return $this->g->generateEmbedding($text);
    }
}
EOF

cat > src/QdrantRepository.php <<'EOF'
<?php
namespace App;
use Qdrant\Qdrant;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\PointStruct;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\VectorStruct;

class QdrantRepository {
    public function __construct(private Qdrant $client, private string $collection = 'faqs') {}

    public function setup(int $dim): void {
        $this->client->collections($this->collection)
            ->create((new \Qdrant\Models\Request\CreateCollection())
            ->addVector(new VectorParams($dim, VectorParams::DISTANCE_COSINE)));
    }

    public function upsert(int $id, array $vector, array $payload = []): void {
        $point = new PointStruct($id, new VectorStruct($vector), $payload);
        $this->client->collections($this->collection)->points()
            ->upsert((new PointsStruct())->addPoint($point));
    }

    public function search(array $vector, int $top = 3): array {
        $resp = $this->client->collections($this->collection)->points()
            ->search((new \Qdrant\Models\Request\SearchRequest(new VectorStruct($vector)))
            ->setLimit($top));
        return $resp['result'];
    }
}
EOF

cat > src/RagService.php <<'EOF'
<?php
namespace App;
use OpenAI\OpenAI;

class RagService {
    public function __construct(
        private OpenAI $openai,
        private EmbedderInterface $embedder,
        private QdrantRepository $repo,
        private string $collection = 'faqs',
        private int $topK = 3
    ) {}

    public function answer(string $userQuery): array {
        $vec = $this->embedder->embed($userQuery);
        $hits = $this->repo->search($vec, $this->topK);
        $context = array_column(array_slice($hits, 0, $this->topK), 'payload.question');
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
EOF

# -- API Endpoint --
cat > api/faq_rag.php <<'EOF'
<?php
require __DIR__.'/../vendor/autoload.php';
use App\{OpenAIEmbedder, QdrantRepository, RagService};
use LLPhant\Embeddings\OpenAIEmbeddingGenerator;
use OpenAI\OpenAI;

Dotenv\Dotenv::createImmutable(__DIR__.'/..')->load();

$embedder = new OpenAIEmbedder(new OpenAIEmbeddingGenerator($_ENV['OPENAI_API_KEY']));
$qdrant = new \Qdrant\Qdrant((new \Qdrant\Http\Builder())->build(new \Qdrant\Config($_ENV['QDRANT_URL'])));
$repo = new QdrantRepository($qdrant);

$rag = new RagService(
    OpenAI::client($_ENV['OPENAI_API_KEY']),
    $embedder,
    $repo,
    'faqs',
    3
);

$q = trim($_GET['q'] ?? '');
if (!$q) { http_response_code(400); echo json_encode(['error'=>'Missing query']); exit; }

header('Content-Type: application/json');
echo json_encode($rag->answer($q));
EOF

# -- Seeding Script --
cat > scripts/seed.php <<'EOF'
<?php
require __DIR__.'/../vendor/autoload.php';
use App\{OpenAIEmbedder, QdrantRepository};
Dotenv\Dotenv::createImmutable(__DIR__.'/..')->load();

$embedder = new OpenAIEmbedder(new LLPhant\Embeddings\OpenAIEmbeddingGenerator($_ENV['OPENAI_API_KEY']));
$qdrant = new \Qdrant\Qdrant((new \Qdrant\Http\Builder())->build(new \Qdrant\Config($_ENV['QDRANT_URL'])));
$repo = new QdrantRepository($qdrant);
$repo->setup(1536);

$faqs = [
  1 => 'How do I reset my password?',
  2 => 'Where can I download my invoice?',
];

foreach ($faqs as $id => $q) {
  $vec = $embedder->embed($q);
  $repo->upsert($id, $vec, ['question'=>$q]);
}
EOF

