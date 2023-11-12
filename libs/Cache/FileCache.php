<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 0:11
 */

namespace Libs\Cache;


use Libs\ShortURL;

/**
 * Class FileCache
 * 文件缓存不支持指定过期时间，采用全局固定的过期时间
 * @package Libs
 */
class FileCache extends Cache
{
    protected $cacheDIR;

    public function __construct()
    {
        $this->cacheDIR = ROOT_PATH . DIRECTORY_SEPARATOR . CACHE_DIR;
        if (!is_dir($this->cacheDIR)) {
            if (!mkdir($this->cacheDIR, 0644)) {
                throw new \RuntimeException("缓存目录不存在");
            }
        }
        $this->cacheDIR = rtrim($this->cacheDIR, '/');
    }

    public function putCache(string $name, $value, int $timeout)
    {
        file_put_contents($this->cacheDIR . DIRECTORY_SEPARATOR . $name . '.data', serialize($value));
    }

    public function getCache(string $name)
    {
        $raw = @file_get_contents($this->cacheDIR . DIRECTORY_SEPARATOR . $name . '.data');
        return unserialize($raw);
    }

    public function hasCache(string $name): bool
    {
        return is_file($this->cacheDIR . DIRECTORY_SEPARATOR . $name . '.data');
    }

    public function clearCache(string $name = null)
    {
        if ($name === null) {
            $list = scandir($this->cacheDIR);
            $currentTime = time();
            foreach ($list as $file) {
                if ($file == '.' or $file == '..' or !preg_match('/^(url|request)_/i', $file))
                    continue;
                $path = $this->cacheDIR . DIRECTORY_SEPARATOR . $file;

                if ($currentTime - filemtime($path) > CACHE_EXPIRE) {
                    if (preg_match('/^((url|request)_[A-z0-9]+)\.[A-z]+$/', $file, $matches)) {
                        $cache = $this->getCache($matches[1]);
                        $url = $cache['url'];
                        $su = new ShortURL();
                        @unlink($path) && $count++ && $su->cleanUrlRecord($url);
                        unset($url);
                    }
                }
            }
        } else {
            $path = $this->cacheDIR . DIRECTORY_SEPARATOR . $name . '.data';
            if (is_file($path)) {
                @unlink($path);
            }
        }

    }


}