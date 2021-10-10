<?php

declare(strict_types=1);

namespace App\Model\Graph;

class Edge implements EdgeInterface
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $target;

    /**
     * @var int
     */
    private $weight;

    /**
     * @param string $source
     * @param string $target
     * @param int $weight
     */
    public function __construct(string $source, string $target, int $weight)
    {
        $this->source = $source;
        $this->target = $target;
        $this->weight = $weight;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * {@inheritdoc}
     */
    public function getWeight(): int
    {
        return $this->weight;
    }
}
