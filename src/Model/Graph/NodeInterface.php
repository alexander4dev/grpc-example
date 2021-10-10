<?php

declare(strict_types=1);

namespace App\Model\Graph;

use SplPriorityQueue;

interface NodeInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return SplPriorityQueue
     */
    public function getSourceNodes(): SplPriorityQueue;

    /**
     * @param NodeInterface $node
     * @param int $weight
     * @return void
     */
    public function addSourceNode(NodeInterface $node, int $weight): void;

    /**
     * @return SplPriorityQueue
     */
    public function getTargetNodes(): SplPriorityQueue;

    /**
     * @param NodeInterface $node
     * @param int $weight
     * @return void
     */
    public function addTargetNode(NodeInterface $node, int $weight): void;
}
