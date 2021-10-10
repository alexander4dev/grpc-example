<?php

declare(strict_types=1);

namespace App\Model\Graph;

interface GraphInterface
{
    /**
     * @param NodeInterface $node
     * @return void
     */
    public function addNode(NodeInterface $node): void;

    /**
     * @param string $nodeId
     * @return bool
     */
    public function hasNode(string $nodeId): bool;

    /**
     * @param string $nodeId
     * @return NodeInterface
     */
    public function getNode(string $nodeId): NodeInterface;

    /**
     * @return NodeInterface[]
     */
    public function getNodes(): array;

    /**
     * @return int
     */
    public function getNodesCount(): int;

    /**
     * @param EdgeInterface $edge
     * @return void
     */
    public function addEdge(EdgeInterface $edge): void;
}
