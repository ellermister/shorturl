<?php
define('ROOT_PATH', __DIR__);
define('PASS', true);// 访问许可
spl_autoload_register(function ($class) {
    require_once __DIR__ . '/' . lcfirst(str_replace('\\', '/', $class) . '.php');
});

include("helper.php");

define('CACHE_TYPE', env('CACHE_TYPE', 'file'));// 支持REDIS OR FILE
define('CACHE_REDIS_KEY', env('CACHE_REDIS_KEY', ''));// REDIS AUTH KEY
define('CACHE_REDIS_HOST', env('CACHE_REDIS_HOST', '127.0.0.1'));
define('CACHE_REDIS_PORT', intval(env('CACHE_REDIS_PORT', 6379)));
define('CACHE_REDIS_PREFIX', env('CACHE_REDIS_PREFIX', 'shorturl'));
define('CACHE_DIR', env('CACHE_DIR', 'cache'));// FILE模式下缓存目录
define('CACHE_EXPIRE', intval(env('CACHE_EXPIRE', 3600 * 24 * 7)));// 缓存时间，单位秒
define('IS_HTTPS', boolval(env('IS_HTTPS', false)));//是否HTTPS
define('VIEW_PATH', 'view');//视图模板目录

define('SUB_PATH', '/');// 子目录： `/` OR `/shorturl/` 必须以 `/` 结尾
define('ENABLE_CLIENT_ENCRYPT', true);
define('ENCRYPT_PASSPHRASE', env('ENCRYPT_PASSPHRASE', 'applicationPassword'));

\Libs\Languages::setConfig(include("lang.php"));
\Libs\Languages::setLocale(get_lang());

//--- 入口逻辑  ---//

try {
    ob_start();
    route('/', function () {
        view('welcome', ['time' => date('Ymd')]);
    });

    route("/s/([A-z0-9]+)", function ($matches) {
        $shortURL = new \Libs\ShortURL();
        $data = $shortURL->hashToUrl($matches[1]);
        // 直接重定向
        $encrypt_type = ['normal'];
        $extent = [];
        if (!empty($data['url'])) {
            $url = $data['url'];
            $encrypt_type = $data['encrypt_type'];
            $extent = $data['extent'] ?? [];
        }
        empty($url) && $url = '/404';

        $redirectRequest = new \Libs\Redirects\RedirectRequest(
            $url,
            $encrypt_type,
            $matches[1],
            $extent,
        );
        $redirectRequest->handle();
    });

    route('/api/link', function ($matches) {
        $url = $_REQUEST['url'] ?? '';
        $encrypt_type = $_REQUEST['encrypt_type'] ?? '["normal"]';
        $extent = $_REQUEST['extent'] ?? '[]';
        if (null == ($encrypt_type = json_decode($encrypt_type, true))) {
            $response = json(__('Wrong encode_type parameter'), 500);
        } else if (empty($url)) {
            $response = json(__('The url cannot be empty'), 500);
        } else if (mb_strlen($url) > 2047) {
            $response = json(__('Too long url'), 500);
        } else if (mb_strlen($extent) > 10000) {
            $response = json(__('Too much content'), 500);
        } else {
            $extent = json_decode($extent, true);
            $su = new \Libs\ShortURL();

            $response = $su->urlToShort($url, $encrypt_type, $extent ?? []);
            $response = json(__('Link created successfully'), 200, $response);
        }
        echo $response;
    });
    route('/api/clean', function () {
        $count = \Libs\Cache\CacheManager::clearCache(null);
        echo json(sprintf('clean %s files', $count), 200);
    });

    route('/404', function () {
        abort(404);
    });
} catch (\RedisException $exception) {
    ob_clean();
    echo sprintf('Redis cannot connect:[%s]%s', $exception->getCode(), $exception->getMessage());
} catch (\Exception $exception) {
    ob_clean();
    echo sprintf('Site Error:[%s]%s', $exception->getCode(), $exception->getMessage());
}

