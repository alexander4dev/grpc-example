<?php

declare(strict_types=1);

namespace App\Autorus;

use Redis;

abstract class AbstractRedis implements RedisInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $db;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @param string $host
     * @param int $port
     * @param string $password
     * @param int $db
     */
    public function __construct(
        string $host,
        int $port,
        string $password,
        int $db
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->db = $db;
    }

    /**
     * @return Redis
     */
    public function getConnection(): Redis
    {
        if (!$this->redis instanceof Redis) {
            $this->redis = new Redis();
            $this->redis->connect($this->host, $this->port);
            $this->redis->auth($this->password);
            $this->redis->select($this->db);
        }

        return $this->redis;
    }
}
