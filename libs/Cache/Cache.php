<?php
namespace Libs\Cache;
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/12
 * Time: 23:58
 */

abstract class Cache
{
    abstract public function putCache(string $name, $value, int $timeout);
    abstract public function getCache(string $name);
    abstract public function hasCache(string $name):bool;
    abstract public function clearCache(string $name);
}