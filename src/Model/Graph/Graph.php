<?php

declare(strict_types=1);

namespace App\Model\Graph;

class Graph implements GraphInterface
{
    /**
     * @var NodeInterface[]
     */
    private $nodes = [];

    /**
     * @var EdgeInterface[]
     */
    private $edges = [];

    /**
     * {@inheritdoc}
     */
    public function addNode(NodeInterface $node): void
    {
        $this->nodes[$node->getId()] = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNode(string $nodeId): bool
    {
        return array_key_exists($nodeId, $this->nodes);
    }

    /**
     * {@inheritdoc}
     */
    public function getNode(string $nodeId): NodeInterface
    {
        return $this->nodes[$nodeId];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesCount(): int
    {
        return count($this->nodes);
    }

    /**
     * {@inheritdoc}
     */
    public function addEdge(EdgeInterface $edge): void
    {
        $sourceNodeId = $edge->getSource();
        $targetNodeId = $edge->getTarget();
        $weight = $edge->getWeight();

        $sourceNode = $this->getNode($sourceNodeId);
        $targetNode = $this->getNode($targetNodeId);

        $sourceNode->addTargetNode($targetNode, $weight);
        $targetNode->addSourceNode($sourceNode, $weight);

        $this->edges[] = $edge;
    }
}
