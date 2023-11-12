<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 1:20
 */

function getRandStr($len)
{
    $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
    $value = '';
    while (strlen($value) < $len) {
        $value .= substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $len);
    }
    return substr($value, 0, $len);
}


function makeMessageJs(string $msg)
{
    $javascript = "(function(){alert(`{{msg}}`)})();";
    $javascript = str_replace('{{msg}}', $msg, $javascript);
    return $javascript;
}

function makeRedirectJs($url, $time = 500)
{
    $javascript = 'setTimeout(function(){location.href="{{url}}"},{{time}});';
    $javascript = str_replace('{{url}}', $url, $javascript);
    $javascript = str_replace('{{time}}', $time, $javascript);
    return $javascript;
}

function makeReturnJs($code, $time = 500)
{
    $javascript = "(function(){return window.atob('{{code}}');})();";
    $javascript = str_replace('{{code}}', base64_encode($code), $javascript);
    return $javascript;
}

function base64_encode_url($input) {
    return strtr(base64_encode($input), '+/=', '._-');
}

function base64_decode_url($input) {
    return base64_decode(strtr($input, '._-', '+/='));
}




/**
 * 判断IP是否大陆
 * https://raw.githubusercontent.com/ym/chnroutes2/master/chnroutes.txt 下载与根目录
 *
 * @param $ip
 * @return bool
 */
function ip_is_china($ip)
{
    $path = ROOT_PATH . '/chnroutes.txt';
    if (is_file($path)) {
        $ipInt = ip2long($ip);
        $fh = fopen($path, 'r') or exit("Unable to open file chnroutes.txt !");;
        while (!feof($fh)) {
            $ipSegment = fgets($fh);
            if (substr($ipSegment, 0, 1) == '#') {
                unset($ipSegment);
                continue;
            }
            list($ipBegin, $type) = explode('/', $ipSegment);
            $ipBegin = ip2long($ipBegin);
            $mask = 0xFFFFFFFF << (32 - intval($type));
            if (intval($ipInt & $mask) == intval($ipBegin & $mask)) {
                unset($raw);
                fclose($fh);
                return true;
            }
            unset($raw);
        }
        fclose($fh);
    }
    return false;
}


function route($uri, Closure $_route)
{
    if (empty($_SERVER['PATH_INFO'])) {
        $pathInfo = $_SERVER['REQUEST_URI'] ?? '/';
    } else {
        $pathInfo = $_SERVER['PATH_INFO'];
    }

    if (substr($pathInfo, 0, strlen(SUB_PATH)) == SUB_PATH) {
        $pathInfo = '/' . ltrim(substr($pathInfo, strlen(SUB_PATH)), '/');
    }

    $pathInfo = preg_replace('/\?.*?$/is', '', $pathInfo);
    if (preg_match('#^' . $uri . '$#', $pathInfo, $matches)) {
        $_route($matches);
        exit(0);
    }
}


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


function get_lang()
{
    $locale = 'en';
    $http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
        "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
        $http_accept_language, $hits, PREG_SET_ORDER);
    if (isset($hits[0])) {
        $locale = $hits[0][1];
    }
    return $locale;
}

function __($name, $_vars = [])
{
    return \Libs\Languages::trans($name, $_vars);
}

function env(string $name, $default)
{
    $value = getenv($name);
    if($value === false){
        return $default;
    }
    return $value;
}