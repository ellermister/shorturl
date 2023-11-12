<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 1:21
 */

namespace Libs;


class ShortURL
{

    public function getRandStr($len)
    {
        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        $value = '';
        while (strlen($value) < $len) {
            $value .= substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $len);
        }
        return substr($value, 0, $len);
    }

    /**
     * 获取一个可用的哈希
     * @return false|string
     */
    public function urlHash()
    {
        $hash = $this->getRandStr(5);
        $cm = new \Libs\Cache\CacheManager();
        while ($cm->hasCache('url_' . $hash)) {
            $hash = $this->getRandStr(5);
        }
        return $hash;
    }


    /**
     * 映射URL和HASH关系
     *
     * @param $url
     * @param $encrypt_type
     * @param $extent
     * @return bool|string
     */
    public function urlToHash($url, $encrypt_type, $extent)
    {
        $hash = $this->urlHash();
        $cm = new \Libs\Cache\CacheManager();
        $cm->putCache('url_' . $hash, ['url' => $url, 'encrypt_type' => $encrypt_type, 'extent' => $extent], -1);
        return $hash;
    }


    /**
     * 从HASH中还原URL
     *
     * @param $hash
     * @return mixed|string
     */
    public function hashToUrl($hash)
    {
        $cm = new \Libs\Cache\CacheManager();
        if ($cm->hasCache('url_' . $hash)) {
            $url = $cm->getCache('url_' . $hash);
        }
        return $url ?? '';
    }


    /**
     * 移除 hash
     * @param string $hash
     */
    public function removeHash(string $hash)
    {
        $cm = new \Libs\Cache\CacheManager();
        $cm->clearCache("url_".$hash);
    }


    /**
     * URL转短链接
     *
     * @param $url
     * @param string $encrypt_type
     * @param string $extent
     * @return string
     */
    public function urlToShort($url, $encrypt_type = 'encrypt', $extent = '')
    {
        if (!preg_match('/^[A-z]+:\/\//i', $url)) {
            $url = 'http://' . $url;
        }
        $id = $this->urlToHash($url, $encrypt_type, $extent);
        $shortUrl = sprintf('%s://%s/%s/%s', IS_HTTPS ? 'https' : 'http', $_SERVER['HTTP_HOST'], ltrim(SUB_PATH, '/') . 's', $id);
        $this->addUrlRecord($url);
        return ($shortUrl);
    }

    /**
     * 自增统计URL生成次数
     *
     * @param $url
     */
    public function addUrlRecord($url)
    {
        $cache = [];
        $cm = new \Libs\Cache\CacheManager();
        if ($cm->hasCache('manage')) {
            $cache = $cm->getCache('manage');
        }
        $cache[$url] = isset($cache[$url]) ? $cache[$url] + 1 : 1;
        $cm = new \Libs\Cache\CacheManager();
        $cm->putCache('manage', $cache, -1);
        $this->addUrlRecordHistory($url);
    }


    function getUrlRecord($url = false)
    {
        $count = 0;
        $cache = [];
        $cm = new \Libs\Cache\CacheManager();
        if ($cm->hasCache('manage')) {
            $cache = $cm->getCache('manage');
        }
        if ($url) {
            $count += $cache[$url];
        } else {
            foreach ($cache as $value) {
                $count += intval($value);
            }
        }

        return $count;
    }

    function cleanUrlRecord($url = false)
    {
        $cache = [];
        $cm = new \Libs\Cache\CacheManager();
        if ($cm->hasCache('manage')) {
            $cache = $cm->getCache('manage');
        }
        if ($url) {
            unset($cache[$url]);
        } else {
            $cache = [];
        }

        $cm = new \Libs\Cache\CacheManager();
        $cm->putCache('manage', $cache, -1);
    }

    /**
     * 自增统计URL历史生成次数
     *
     * @param $url
     */
    function addUrlRecordHistory($url)
    {
        $cache = [];
        $cm = new \Libs\Cache\CacheManager();
        if ($cm->hasCache('history')) {
            $cache = $cm->getCache('history');
        }
        $cache['count'] = isset($cache['count']) ? $cache['count'] + 1 : 1;
        $cache['url'][] = $url;

        $cm = new \Libs\Cache\CacheManager();
        $cm->putCache('history', $cache, -1);
    }

    function getUrlRecordHistory()
    {
        $cache = [];
        $cm = new \Libs\Cache\CacheManager();
        if ($cm->hasCache('history')) {
            $cache = $cm->getCache('history');
        }
        return $cache['count'] ?? 0;
    }
}