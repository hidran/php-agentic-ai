<?php
namespace App;

use Qdrant\Models\Request\SearchRequest;
use Qdrant\Qdrant;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\PointStruct;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\VectorStruct;

class QdrantRepository
{
    private Qdrant $client;
    private string $collection;

    public function __construct(Qdrant $client, string $collection = 'faqs')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Set up the Qdrant collection with the specified vector dimension.
     */
    public function setup(int $dim): void
    {
        // Check if the collection already exists
        $exists = $this->client
            ->collections($this->collection)
            ->exists();

        if ($exists['result']['exists'] ?? false) {
            // Already created — no need to recreate
            return;
        }

        $create = new \Qdrant\Models\Request\CreateCollection();
        $create->addVector(new VectorParams($dim, VectorParams::DISTANCE_COSINE));

        $this->client
            ->collections($this->collection)
            ->create($create);
    }


    /**
     * Upsert a single point into the collection.
     *
     * @param int $id     The numeric point ID.
     * @param array $vector   The embedding vector.
     * @param array $payload  Additional metadata to store with the point.
     */
    public function upsert(int $id, array $vector, array $payload = []): void
    {
        $points = new PointsStruct();
        $point = new PointStruct(
            $id,
            new VectorStruct($vector),
            $payload
        );
        $points->addPoint($point);

        $this->client
            ->collections($this->collection)
            ->points()
            ->upsert($points);
    }

    /**
     * Search for nearest points by vector similarity.
     *
     * @param array $vector  The query embedding vector.
     * @param int $top       Maximum number of results to return.
     * @return array         Raw search result array.
     */
    public function search(array $vector, int $top = 3): array
    {
        $searchRequest = new SearchRequest(
            new VectorStruct($vector)
        );
        $searchRequest->setLimit($top);
        $searchRequest->setWithPayload(true); // <-- this line is key

        $resp = $this->client
            ->collections($this->collection)
            ->points()
            ->search($searchRequest);

        return $resp['result'] ?? [];
    }
}
