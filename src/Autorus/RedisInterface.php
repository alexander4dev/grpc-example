<?php

declare(strict_types=1);

namespace App\Autorus;

use Redis;

interface RedisInterface
{
    public function getConnection(): Redis;
}
