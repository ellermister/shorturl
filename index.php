<?php
define('ROOT_PATH', __DIR__);
define('CACHE_TYPE', 'file');// 支持REDIS OR FILE
define('CACHE_DIR', 'cache');// FILE模式下缓存目录
define('CACHE_EXPIRE', 3600 * 24 * 7);// 缓存时间，单位秒
define('IS_HTTPS', false);//是否HTTPS
define('VIEW_PATH', 'view');//视图模板目录
define('PASS', true);// 访问许可

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

function hasCache($name)
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
        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        $raw = @file_get_contents($dir . DIRECTORY_SEPARATOR . $name . '.data');
        return unserialize($raw);
    } else if ($engine == 'redis') {

    }
}

function clearCache()
{
    $currentTime = time();
    $count = 0;
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        $list = scandir($dir);
        foreach ($list as $file) {
            if ($file == '.' or $file == '..' or !preg_match('/^url_/i', $file))
                continue;
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if ($currentTime - filemtime($path) > CACHE_EXPIRE) {
                if (preg_match('/^(url_[A-z0-9]+)\.[A-z]+$/', $file, $matches)) {
                    $url = getCache($matches[1]);
                    unlink($path) && $count++ && cleanUrlRecord($url);
                    unset($url);
                }
            }
        }
    }
    return $count;
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

function urlHash()
{
    $hash = getRandStr(5);
    while (hasCache('url_' . $hash)) {
        $hash = getRandStr(5);
    }
    return $hash;
}

function urlToHash($url)
{
    $hash = urlHash();
    putCache('url_' . $hash, $url);
    return $hash;
}

function hashToUrl($hash)
{
    if (hasCache('url_' . $hash)) {
        $url = getCache('url_' . $hash);
    }
    return $url ?? '';
}

function urlToShort($url)
{
    if (!preg_match('/^[A-z]+:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    $id = urlToHash($url);
    $shortUrl = sprintf('%s://%s/%s/%s', IS_HTTPS ? 'https' : 'http', $_SERVER['HTTP_HOST'], 's', $id);
    addUrlRecord($url);
    return ($shortUrl);
}

function addUrlRecord($url)
{
    $cache = [];
    if (hasCache('manage')) {
        $cache = getCache('manage');
    }
    $cache[$url] = isset($cache[$url]) ? $cache[$url] + 1 : 1;
    putCache('manage', $cache);
    addUrlRecordHistory($url);
}

function getUrlRecord($url = false)
{
    $count = 0;
    $cache = [];
    if (hasCache('manage')) {
        $cache = getCache('manage');
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
    if (hasCache('manage')) {
        $cache = getCache('manage');
    }
    if ($url) {
        unset($cache[$url]);
    } else {
        $cache[$url] = [];
    }
    putCache('manage', $cache);
}

function addUrlRecordHistory($url)
{
    $cache = [];
    if (hasCache('history')) {
        $cache = getCache('history');
    }
    $cache['count'] = isset($cache['count']) ? $cache['count'] + 1 : 1;
    $cache['url'][] = $url;
    putCache('history', $cache);
}

function getUrlRecordHistory()
{
    $cache = [];
    if (hasCache('history')) {
        $cache = getCache('history');
    }
    return $cache['count'] ?? 0;
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

function view($name, $vars = [])
{
    $path = ROOT_PATH . DIRECTORY_SEPARATOR . VIEW_PATH . DIRECTORY_SEPARATOR . $name . '.php';
    extract($vars);
    @include $path;
}

function abort($status = 404)
{
    $path = ROOT_PATH . DIRECTORY_SEPARATOR . $status . '.html';
    if (is_file($path)) {
        @include path;
    } else {
        echo $status;
    }
    die();
}

function route($uri, Closure $_route)
{
    $pathInfo = $_SERVER['PATH_INFO'] ?? '/';
    if (preg_match('#^' . $uri . '$#', $pathInfo, $matches)) {
        $_route($matches);
        exit(0);
    }
}


//--- 入口逻辑  ---//
$pathInfo = $_SERVER['PATH_INFO'] ?? '/';

route('/', function () {
    view('welcome', ['time' => date('Ymd')]);
});

route("/s/([A-z0-9]+)", function ($matches) {
    $url = hashToUrl($matches[1]);
    empty($url) && $url = '/404';
    header('Location: ' . $url);
});

route('/api/link', function ($matches) {
    $url = $_REQUEST['url'] ?? '';
    if (empty($url)) {
        $response = json('url不能为空', 500);
    } else {
        $response = urlToShort($url);
        $response = json('生成完毕', 200, $response);
    }
    echo $response;
});
route('/api/clean', function () {
    $count = clearCache();
    echo json(sprintf('clean %s files', $count), 200);
});

route('/404', function () {
    abort(404);
});
