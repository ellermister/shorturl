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

function clearCache($name = null)
{
    $currentTime = time();
    $count = 0;
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;

        if ($name == null) {
            $list = scandir($dir);
            foreach ($list as $file) {
                if ($file == '.' or $file == '..' or !preg_match('/^(url|request)_/i', $file))
                    continue;
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if ($currentTime - filemtime($path) > CACHE_EXPIRE) {
                    if (preg_match('/^((url|request)_[A-z0-9]+)\.[A-z]+$/', $file, $matches)) {
                        $cache = getCache($matches[1]);
                        $url = $cache['url'];
                        unlink($path) && $count++ && cleanUrlRecord($url);
                        unset($url);
                    }
                }
            }
        } else {
            $path = $dir . DIRECTORY_SEPARATOR . $name . '.data';
            @unlink($path);
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

function urlToHash($url, $encrypt_type)
{
    $hash = urlHash();
    putCache('url_' . $hash, ['url' => $url, 'encrypt_type' => $encrypt_type]);
    return $hash;
}

function hashToUrl($hash)
{
    if (hasCache('url_' . $hash)) {
        $url = getCache('url_' . $hash);
    }
    return $url ?? '';
}

function urlToShort($url, $encrypt_type = 'encrypt')
{
    if (!preg_match('/^[A-z]+:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    $id = urlToHash($url, $encrypt_type);
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
        $cache = [];
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

//--- 加密相关 ---//

function charCodeAt($str, $index)
{
    $char = mb_substr($str, $index, 1, 'UTF-8');
    if (mb_check_encoding($char, 'UTF-8')) {
        $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
        return hexdec(bin2hex($ret));
    } else {
        return null;
    }
}

function uchr($codes)
{
    if (is_scalar($codes)) $codes = func_get_args();
    $str = '';
    foreach ($codes as $code) {
        $buf = html_entity_decode('&#' . $code . ';', ENT_NOQUOTES, 'UTF-8');
        $buf == '&#' . $code . ';' && ($buf = mb_convert_encoding('&#' . intval($code) . ';', 'UTF-8', 'HTML-ENTITIES'));
        $str .= $buf;
    }
    return $str;
}

function aaEncode($javascript)
{
    $b = [
        "(c^_^o)",
        "(ﾟΘﾟ)",
        "((o^_^o) - (ﾟΘﾟ))",
        "(o^_^o)",
        "(ﾟｰﾟ)",
        "((ﾟｰﾟ) + (ﾟΘﾟ))",
        "((o^_^o) +(o^_^o))",
        "((ﾟｰﾟ) + (o^_^o))",
        "((ﾟｰﾟ) + (ﾟｰﾟ))",
        "((ﾟｰﾟ) + (ﾟｰﾟ) + (ﾟΘﾟ))",
        "(ﾟДﾟ) .ﾟωﾟﾉ",
        "(ﾟДﾟ) .ﾟΘﾟﾉ",
        "(ﾟДﾟ) ['c']",
        "(ﾟДﾟ) .ﾟｰﾟﾉ",
        "(ﾟДﾟ) .ﾟДﾟﾉ",
        "(ﾟДﾟ) [ﾟΘﾟ]"
    ];
    $r = "ﾟωﾟﾉ= /｀ｍ´）ﾉ ~┻━┻   //*´∇｀*/ ['_']; o=(ﾟｰﾟ)  =_=3; c=(ﾟΘﾟ) =(ﾟｰﾟ)-(ﾟｰﾟ); ";
    if (preg_match('/ひだまりスケッチ×(365|３５６)\s*来週も見てくださいね[!！]/', $javascript)) {
        $r .= "X=_=3; ";
        $r .= "\r\n\r\n    X / _ / X < \"来週も見てくださいね!\";\r\n\r\n";
    }
    $r .= "(ﾟДﾟ) =(ﾟΘﾟ)= (o^_^o)/ (o^_^o);" .
        "(ﾟДﾟ)={ﾟΘﾟ: '_' ,ﾟωﾟﾉ : ((ﾟωﾟﾉ==3) +'_') [ﾟΘﾟ] " .
        ",ﾟｰﾟﾉ :(ﾟωﾟﾉ+ '_')[o^_^o -(ﾟΘﾟ)] " .
        ",ﾟДﾟﾉ:((ﾟｰﾟ==3) +'_')[ﾟｰﾟ] }; (ﾟДﾟ) [ﾟΘﾟ] =((ﾟωﾟﾉ==3) +'_') [c^_^o];" .
        "(ﾟДﾟ) ['c'] = ((ﾟДﾟ)+'_') [ (ﾟｰﾟ)+(ﾟｰﾟ)-(ﾟΘﾟ) ];" .
        "(ﾟДﾟ) ['o'] = ((ﾟДﾟ)+'_') [ﾟΘﾟ];" .
        "(ﾟoﾟ)=(ﾟДﾟ) ['c']+(ﾟДﾟ) ['o']+(ﾟωﾟﾉ +'_')[ﾟΘﾟ]+ ((ﾟωﾟﾉ==3) +'_') [ﾟｰﾟ] + " .
        "((ﾟДﾟ) +'_') [(ﾟｰﾟ)+(ﾟｰﾟ)]+ ((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+" .
        "((ﾟｰﾟ==3) +'_') [(ﾟｰﾟ) - (ﾟΘﾟ)]+(ﾟДﾟ) ['c']+" .
        "((ﾟДﾟ)+'_') [(ﾟｰﾟ)+(ﾟｰﾟ)]+ (ﾟДﾟ) ['o']+" .
        "((ﾟｰﾟ==3) +'_') [ﾟΘﾟ];(ﾟДﾟ) ['_'] =(o^_^o) [ﾟoﾟ] [ﾟoﾟ];" .
        "(ﾟεﾟ)=((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+ (ﾟДﾟ) .ﾟДﾟﾉ+" .
        "((ﾟДﾟ)+'_') [(ﾟｰﾟ) + (ﾟｰﾟ)]+((ﾟｰﾟ==3) +'_') [o^_^o -ﾟΘﾟ]+" .
        "((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+ (ﾟωﾟﾉ +'_') [ﾟΘﾟ]; " .
        "(ﾟｰﾟ)+=(ﾟΘﾟ); (ﾟДﾟ)[ﾟεﾟ]='\\\\'; " .
        "(ﾟДﾟ).ﾟΘﾟﾉ=(ﾟДﾟ+ ﾟｰﾟ)[o^_^o -(ﾟΘﾟ)];" .
        "(oﾟｰﾟo)=(ﾟωﾟﾉ +'_')[c^_^o];" .
        "(ﾟДﾟ) [ﾟoﾟ]='\\\"';" .
        "(ﾟДﾟ) ['_'] ( (ﾟДﾟ) ['_'] (ﾟεﾟ+";
    $r .= "(ﾟДﾟ)[ﾟoﾟ]+ ";

    for ($i = 0; $i < mb_strlen($javascript); $i++) {
        $n = charCodeAt($javascript, $i);
        $t = "(ﾟДﾟ)[ﾟεﾟ]+";
        if ($n <= 127) {
            $t .= preg_replace_callback('/[0-7]/', function ($c) use ($b) {
                return $b[$c[0]] . "+ ";
            }, ((string)decoct($n)));
        } else {
            if (preg_match('/[0-9a-f]{4}$/', '000' . ((string)dechex($n)), $result)) {
                $m = $result[0];
            } else {
                $m = '';
            }
            $t .= "(oﾟｰﾟo)+ " . preg_replace_callback('/[0-9a-f]/i', function ($c) use ($b) {
                    return $b[hexdec($c[0])] . "+ ";
                }, $m);
        }
        $r .= $t;
    }

    $r .= "(ﾟДﾟ)[ﾟoﾟ]) (ﾟΘﾟ)) ('_');";
    return $r;

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
    $pathInfo = $_SERVER['PATH_INFO'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
    $pathInfo = preg_replace('/\?.*?$/is', '', $pathInfo);
    if (preg_match('#^' . $uri . '$#', $pathInfo, $matches)) {
        $_route($matches);
        exit(0);
    }
}

function redirect($url, $encrypt_type, $hash)
{
    if ($encrypt_type == 'normal')
        header('Location: ' . $url);
    else if ($encrypt_type == 'dynamic') {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{title}}</title><meta name="referrer" content="no-referrer" /></head><body><script type="text/javascript">setTimeout(function(){location.href="{{url}}"},500);</script></body></html>';
        $html = str_replace('{{title}}', 'web redirection...', $html);
        $html = str_replace('{{url}}', $url, $html);
        echo $html;
    } else if ($encrypt_type == "encrypt") {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{title}}</title><meta name="referrer" content="no-referrer" /></head><body><script src="/request/{{request_id}}"type="text/javascript"charset="utf-8"></script></body></html>';
        $javascript = 'setTimeout(function(){location.href="{{url}}"},500);';
        $javascript = str_replace('{{url}}', $url, $javascript);

        $request_id = getRandStr(20);
        putCache('request_' . $request_id, ['js' => aaEncode($javascript), 'hash' => $hash]);
        $html = str_replace('{{title}}', 'web redirection...', $html);
        $html = str_replace('{{request_id}}', $request_id, $html);
        echo $html;
    } else if ($encrypt_type == "once") {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{title}}</title><meta name="referrer" content="no-referrer" /></head><body><script src="/request/{{request_id}}"type="text/javascript"charset="utf-8"></script></body></html>';
        $javascript = 'setTimeout(function(){location.href="{{url}}"},500);';
        $javascript = str_replace('{{url}}', $url, $javascript);

        $request_id = getRandStr(20);
        putCache('request_' . $request_id, ['js' => aaEncode($javascript), 'hash' => $hash, 'clean' => 1]);
        $html = str_replace('{{title}}', 'web redirection...', $html);
        $html = str_replace('{{request_id}}', $request_id, $html);
        echo $html;
    }
}

function responseJavascript($requestId)
{
    $name = 'request_' . $requestId;
    $cache = getCache($name);
    if (empty($cache)) {
        $javascript = 'alert("Invalid request")';
    } else {
        $javascript = $cache['js'];
    }
    if (!empty($cache['clean'])) {
        $data = hashToUrl($cache['hash']);
        clearCache('url_' . $cache['hash']);
        cleanUrlRecord($data['url']);
    }
    clearCache($name);
    echo $javascript;
}


//--- 入口逻辑  ---//
$pathInfo = $_SERVER['PATH_INFO'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
$pathInfo = preg_replace('/\?.*?$/is', '', $pathInfo);

route('/', function () {
    view('welcome', ['time' => date('Ymd')]);
});

route("/s/([A-z0-9]+)", function ($matches) {
    $data = hashToUrl($matches[1]);
    $encrypt_type = 'normal';
    if (!empty($data['url'])) {
        $url = $data['url'];
        $encrypt_type = $data['encrypt_type'];
    }
    empty($url) && $url = '/404';
    redirect($url, $encrypt_type, $matches[1]);
});

route("/request/([A-z0-9]+)", function ($matches) {
    $request_id = $matches[1];
    responseJavascript($request_id);
});

route('/api/link', function ($matches) {
    $url = $_REQUEST['url'] ?? '';
    $encrypt_type = $_REQUEST['encrypt_type'] ?? 'normal';
    if (empty($url)) {
        $response = json('url不能为空', 500);
    } else {
        $response = urlToShort($url, $encrypt_type);
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
