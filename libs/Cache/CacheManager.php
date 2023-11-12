<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 0:29
 */

namespace Libs\Cache;

/**
 * Class CacheManager
 * @package Libs
 *
 * @method static void putCache(string $name, $value, int $timeout)
 * @method static mixed getCache(string $name)
 * @method  static bool hasCache(string $name)
 * @method  static int clearCache(string $name = null)
 */
class CacheManager
{
    protected $provider;

    private static $instance;

    public function __construct()
    {
        $engine = strtolower(CACHE_TYPE);;
        if (!in_array(CACHE_TYPE, ['file', 'redis'])) {
            throw new \RuntimeException(sprintf('不支持的缓存方法: %s', $engine));
        }

        if ($engine == 'file') {
            $this->provider = new FileCache();
        }
        if ($engine == 'redis') {
            $this->provider = new RedisCache();
        }
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->provider, $name], $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        if(!self::$instance){
            self::$instance = new static();
        }
        return call_user_func_array([self::$instance->provider, $name], $arguments);
    }
}