<?php
define('ROOT_PATH', __DIR__);
define('CACHE_TYPE', 'file');// 支持REDIS OR FILE
define('CACHE_DIR', 'cache');// FILE模式下缓存目录
define('IS_HTTPS', false);//是否HTTPS

//--- 缓存相关 ---//

function getCacheType()
{
    $engine = CACHE_TYPE;
    if (!in_array(CACHE_TYPE, ['file', 'redis'])) {
        $engine = 'file';
    }
    return strtolower($engine);
}

function putCache($name, $value)
{
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        file_put_contents($dir . DIRECTORY_SEPARATOR . $name . '.data', serialize($value));
    } else if ($engine == 'redis') {

    }
}

function existsCache($name)
{
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        return is_file($dir . DIRECTORY_SEPARATOR . $name . '.data');
    } else if ($engine == 'redis') {

    }
    return false;
}

function getCache($name)
{
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        if(!is_dir($dir)){
            @mkdir($dir);
        }
        $raw = @file_get_contents($dir . DIRECTORY_SEPARATOR . $name . '.data');
        return unserialize($raw);
    } else if ($engine == 'redis') {

    }
}

//--- URL转换相关 ---//
function getRandStr($len)
{
    $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
    $value = '';
    while (strlen($value) < $len) {
        $value .= substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $len);
    }
    return substr($value, 0, $len);
}

function urlToHash($url)
{
    $hash = getRandStr(5);
    while (existsCache($hash)) {
        $hash = getRandStr(5);
    }
    putCache($hash, $url);
    return $hash;
}

function hashToUrl($hash)
{
    if (existsCache($hash)) {
        $url = getCache($hash);
    }
    return $url ?? '';
}

function urlToShort($url)
{
    $id = urlToHash($url);
    $shortUrl = sprintf('%s://%s/%s/%s', IS_HTTPS ? 'https' : 'http', $_SERVER['HTTP_HOST'], 's', $id);
    return ($shortUrl);
}


//--- Response 相关 ---//

function json($msg, $code = 200, $data = [])
{
    $format = [
        'msg'  => $msg,
        'code' => $code,
        'data' => $data
    ];
    return json_encode($format, JSON_UNESCAPED_UNICODE);
}


//--- 入口逻辑  ---//
$pathInfo = $_SERVER['PATH_INFO'] ?? '/';

if (preg_match('/^\/s\/([A-z0-9]+)/i', $pathInfo, $matches)) {
    //redirect
    $url = hashToUrl($matches[1]);
    empty($url) && $url = '/404';
    header('Location: ' . $url);

} elseif (preg_match('/^\/api\/([A-z0-9]+)/i', $pathInfo, $matches)) {
    //API REQUEST
    if ($matches[1] == 'link') {
        $url = $_REQUEST['url'] ?? '';
        if (empty($url)) {
            $response = json('url不能为空', 500);
        } else {
            $response = urlToShort($url);
            $response = json('ok', 200, $response);
        }
    }
} else if (preg_match('/^\/404$/i', $pathInfo, $matches)) {
    $response = is_file('404.html') ? file_get_contents('404.html') : '404 pages';
} else {
    $response = is_file('welcome.html') ? file_get_contents('welcome.html') : 'Hi!';
}
echo $response;
