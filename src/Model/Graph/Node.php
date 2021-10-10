<?php

declare(strict_types=1);

namespace App\Model\Graph;

use SplPriorityQueue;

class Node implements NodeInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var SplPriorityQueue
     */
    private $sourceNodes;

    /**
     * @var SplPriorityQueue
     */
    private $targetNodes;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
        $this->sourceNodes = new SplPriorityQueue();
        $this->targetNodes = new SplPriorityQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceNodes(): SplPriorityQueue
    {
        return $this->sourceNodes;
    }

    /**
     * {@inheritdoc}
     */
    public function addSourceNode(NodeInterface $node, int $weight): void
    {
        $this->sourceNodes->insert($node, $weight);
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetNodes(): SplPriorityQueue
    {
        return $this->targetNodes;
    }

    /**
     * {@inheritdoc}
     */
    public function addTargetNode(NodeInterface $node, int $weight): void
    {
        $this->targetNodes->insert($node, $weight);
    }
}
