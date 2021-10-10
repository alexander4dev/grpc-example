<?php

declare(strict_types=1);

namespace App\Model\Graph;

interface EdgeInterface
{
    /**
     * @return string
     */
    public function getSource(): string;

    /**
     * @return string
     */
    public function getTarget(): string;

    /**
     * @return int
     */
    public function getWeight(): int;
}
