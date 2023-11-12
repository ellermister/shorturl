<?php

namespace Libs\Cache;

use RuntimeException;

/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/12
 * Time: 23:57
 */
class RedisCache extends Cache
{

    /**
     * @var \Redis
     */
    protected $redis;

    public function __construct()
    {
        $this->redis = $this->getConnect();
    }

    /**
     * @return \Redis
     */
    private function getConnect(): \Redis
    {
        $redis = new \Redis();
        $redis->connect(CACHE_REDIS_HOST, CACHE_REDIS_PORT);
        CACHE_REDIS_KEY && $redis->auth(CACHE_REDIS_KEY);
        if ($redis->isConnected()) {
            return $redis;
        }
        throw new RuntimeException("Redis 连接失败!",);
    }


    public function putCache(string $name, $value, int $timeout)
    {
        $value = serialize($value);
        $this->redis->set(CACHE_REDIS_PREFIX . $name, $value, $timeout);
    }

    public function getCache(string $name)
    {
        $value = $this->redis->get(CACHE_REDIS_PREFIX . $name);
        return unserialize($value);
    }

    public function hasCache(string $name): bool
    {
        return $this->redis->exists(CACHE_REDIS_PREFIX . $name);
    }

    public function clearCache(string $name = null): int
    {
        $count = 0;
        if ($name === null) {
            $iterator = null;
            while ($list = $this->redis->scan($iterator, CACHE_REDIS_PREFIX . '*', 200)) {
                $chunk_keys = array_chunk($list, 50);
                foreach ($chunk_keys as $keys) {
                    $this->redis->del(...$keys);
                    $count += count($keys);
                }
            }
        } else {
            $count = $this->redis->del(CACHE_REDIS_PREFIX . $name);
        }

        return $count;
    }


}