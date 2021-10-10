<?php

declare(strict_types=1);

namespace App\Model\Graph\Algorithm;

use App\Model\Graph\GraphInterface;
use App\Model\Graph\NodeInterface;
use SplPriorityQueue;
use SplStack;

class Dijkstra
{
    /**
     * @var GraphInterface
     */
    private $graph;

    /**
     * @param GraphInterface $graph
     */
    public function __construct(GraphInterface $graph)
    {
        $this->graph = $graph;
    }

    /**
     * @param string $source
     * @param string $target
     * @return SplStack
     */
    public function getPath(string $source, string $target): SplStack
    {
        $result = new SplStack();
        $visited = [];
        $path = [];
        $queue = [];
        $nodes = $this->graph->getNodes();

        foreach ($nodes as $node) {
            /* @var $node NodeInterface */
            $queue[$node->getId()] = INF;
        }

        $queue[$source] = 0;
        $nodesCount = $this->graph->getNodesCount();

        while (count($visited) < $nodesCount) {
            $minSource = array_search(min($queue), $queue, true);
            $minSourceNode = $this->graph->getNode($minSource);
            $targetNodes = $minSourceNode->getTargetNodes();
            $targetNodes->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

            while ($targetNodes->valid()) {
                $targetNodeExtract = $targetNodes->extract();
                /* @var $targetNode NodeInterface */
                $targetNode = $targetNodeExtract['data'];
                $targetNodeCost = $targetNodeExtract['priority'];
                $targetNodeId = $targetNode->getId();

                if (in_array($targetNodeId, $visited)) {
                    continue;
                }

                $minSourceCost = $queue[$minSource];

                if ($minSourceCost + $targetNodeCost < $queue[$targetNodeId]) {
                    $queue[$targetNodeId] = $minSourceCost + $targetNodeCost;
                    $path[$targetNodeId] = $minSource;
                }
            }

            $visited[] = $minSource;
            unset($queue[$minSource]);
        }

        if (!array_key_exists($target, $path)) {
            return $result;
        }

        $pos = $target;

        while ($pos !== $source) {
            $result->push($pos);
            $pos = $path[$pos];
        }

        $result->push($source);

        return $result;
    }
}
