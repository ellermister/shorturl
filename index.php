<?php
define('ROOT_PATH', __DIR__);
define('CACHE_TYPE', 'file');// 支持REDIS OR FILE
define('CACHE_DIR', 'cache');// FILE模式下缓存目录
define('CACHE_EXPIRE', 3600 * 24 * 7);// 缓存时间，单位秒
define('IS_HTTPS', false);//是否HTTPS
define('VIEW_PATH', 'view');//视图模板目录
define('PASS', true);// 访问许可

// --- 语言相关  ---//
$lang_package = [
    'en' => [
    ],
    'zh' => [
        'GENERATE'             => '生成',
        'GITHUB'               => 'Github 地址',
        'ABOUT'                => '关于',
        'GENERATE SHORT URL'   => '生成短网址',
        'Quickly generate URL' => '快速生成URL',
        'Enter URL link'       => '输出URL链接',
        'Generate'             => '生成',
        'normal'               => '原始',
        'no referer'           => '无Referer',
        'encrypt redirect'     => '加密跳转',
        'redirect once'        => '阅后即焚',
        'password access'      => '密码访问',
        'whisper text'         => '附加图文',
        'PC access only'       => '仅限PC访问',
        'Mobile access only'   => '仅限手机访问',

        'Jump directly to the website'           => '直接跳转到目标网站',
        'No Referer parameter'                   => '无 Referer 参数，目标网站无法获取来源站地址',
        'Encrypted access, anti-crawler'         => '加密跳转参数信息，反大部分爬虫抓取探测',
        'Jump only once'                         => '一次性跳转(阅后即焚)',
        'Password required'                      => '将为你生成密码，访问时需要密码验证',
        'Append rich text information'           => '附加富文本信息，您可以在此留言并分享给您的其他社交媒体用户',
        'Only PC users can access this page'     => '仅限PC用户访问该地址',
        'Only Mobile users can access this page' => '仅限手机用户访问该地址',
        'mainland China access only'             => '仅限中国大陆访问',
        'Non-mainland China access only'         => '仅限非中国大陆访问',

        'Access only to users in mainland China'          => '仅限中国大陆用户访问',
        'Only access users who are not in mainland China' => '仅限非中国大陆用户访问',

        'This site generates a total of :url_record_history links，Currently active :url_active_history' => '当前站点历史生成链接:url_record_history个，当前有效:url_active_history个',
    ],
    'ja' => [
        'GENERATE'                       => '生成',
        'GITHUB'                         => 'Github',
        'ABOUT'                          => 'ついて',
        'GENERATE SHORT URL'             => '短いURLを生成する',
        'Quickly generate URL'           => 'URLをすばやく生成する',
        'Enter URL link'                 => 'URLリンクを入力します',
        'Generate'                       => '生成',
        'normal'                         => 'デフォルト',
        'no referer'                     => '「Referer」パラメータなし',
        'encrypt redirect'               => '暗号化されたアクセス',
        'redirect once'                  => '1回限りの訪問',
        'password access'                => 'パスワードの検証',
        'whisper text'                   => '追加テキスト',
        'PC access only'                 => 'PCアクセスのみ',
        'Mobile access only'             => 'モバイルアクセスのみ',
        'mainland China access only'     => '中国本土のユーザーのみがアクセス可能',
        'Non-mainland China access only' => '中国本土以外のユーザーに限定',

        'Jump directly to the website'           => 'ターゲットのWebサイトに直接ジャンプします',
        'No Referer parameter'                   => '「Referer」パラメータがないと、ターゲットWebサイトは送信元ステーションのアドレスを取得できません',
        'Encrypted access, anti-crawler'         => '暗号化されたジャンプパラメータ情報、ほとんどのクローラーの検出防止',
        'Jump only once'                         => 'リンクには一度しかアクセスできず、非常に安全です',
        'Password required'                      => 'リンクのパスワードを生成し、アクセス時に確認します',
        'Append rich text information'           => 'テキストメッセージを残すことができます',
        'Only PC users can access this page'     => 'このアドレスにアクセスできるのはPCユーザーのみです',
        'Only Mobile users can access this page' => 'このアドレスにアクセスできるのは携帯電話ユーザーのみです',

        'Access only to users in mainland China'          => '中国本土のユーザーのみがアクセス可能',//このウェブサイトは中国本土でのみアクセスできます
        'Only access users who are not in mainland China' => '中国本土以外のユーザーに限定',

        'This site generates a total of :url_record_history links，Currently active :url_active_history' => '現在のサイト履歴生成リンク:url_record_historyつのリンク、現在有効:url_active_historyつのリンク',
    ]
];

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
    global $lang_package;
    $locale = get_lang();
    if (!isset($lang_package[$locale])) {
        $locale = 'en';
    }
    if (!isset($lang_package[$locale][$name])) {
        $locale = 'en';
    }
    $text = $lang_package[$locale][$name] ?? $name;
    foreach ($_vars as $name => $value) {
        $text = str_replace(":$name", $value, $text);
    }
    return $text;
}

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

function urlToHash($url, $encrypt_type, $extent)
{
    $hash = urlHash();
    putCache('url_' . $hash, ['url' => $url, 'encrypt_type' => $encrypt_type, 'extent' => $extent]);
    return $hash;
}

function hashToUrl($hash)
{
    if (hasCache('url_' . $hash)) {
        $url = getCache('url_' . $hash);
    }
    return $url ?? '';
}

function urlToShort($url, $encrypt_type = 'encrypt', $extent = '')
{
    if (!preg_match('/^[A-z]+:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    $id = urlToHash($url, $encrypt_type, $extent);
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

function redirect($url, $encrypt_type, $hash, $extent = '')
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
        $javascript = makeRedirectJs($url);

        $request_id = getRandStr(20);
        putCache('request_' . $request_id, ['js' => aaEncode($javascript), 'hash' => $hash]);
        $html = str_replace('{{title}}', 'web redirection...', $html);
        $html = str_replace('{{request_id}}', $request_id, $html);
        echo $html;
    } else if ($encrypt_type == "once") {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{title}}</title><meta name="referrer" content="no-referrer" /></head><body><script src="/request/{{request_id}}"type="text/javascript"charset="utf-8"></script></body></html>';
        $javascript = makeRedirectJs($url);

        $request_id = getRandStr(20);
        putCache('request_' . $request_id, ['js' => aaEncode($javascript), 'hash' => $hash, 'clean' => 1]);
        $html = str_replace('{{title}}', 'web redirection...', $html);
        $html = str_replace('{{request_id}}', $request_id, $html);
        echo $html;
    } else if ($encrypt_type == 'password') {
        $request_id = getRandStr(20);
        putCache('request_' . $request_id, ['hash' => $hash]);
        $data = ['request_id' => $request_id];
        view('password', $data);
    } else if ($encrypt_type == 'whisper') {
        $request_id = getRandStr(20);
        putCache('request_' . $request_id, ['hash' => $hash]);
        $data = ['request_id' => $request_id];
        view('whisper', $data);
    }
}

function responseJavascript($requestId)
{
    $name = 'request_' . $requestId;
    $cache = getCache($name);
    if (empty($cache)) {
        $javascript = 'alert("Invalid request")';
    } else {
        $javascript = $cache['js'] ?? '';
    }

    // 判断是否密码验证
    $data = hashToUrl($cache['hash']);
    if ($data['encrypt_type'] == 'password') {
        if ($data['extent'] != $_REQUEST['password'] ?? '') {
            echo json("密码验证失败", 500, '');
        } else {
            $javascript = aaEncode(makeRedirectJs($data['url']));
            echo json('ok', 200, $javascript);
        }

    } else if ($data['encrypt_type'] == 'whisper') {
        $javascript = makeReturnJs(json_encode($data));
        echo json('ok', 200, $javascript);
    } else {
        echo $javascript;
    }
    if (!empty($cache['clean'])) {
        clearCache('url_' . $cache['hash']);
        cleanUrlRecord($data['url']);
    }
    clearCache($name);
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
        $extent = $data['extent'] ?? '';
    }
    empty($url) && $url = '/404';
    redirect($url, $encrypt_type, $matches[1], $extent);
});

route("/request/([A-z0-9]+)", function ($matches) {
    $request_id = $matches[1];
    responseJavascript($request_id);
});

route('/api/link', function ($matches) {
    $url = $_REQUEST['url'] ?? '';
    $encrypt_type = $_REQUEST['encrypt_type'] ?? 'normal';
    $extent = $_REQUEST['extent'] ?? '';
    if (empty($url)) {
        $response = json('url不能为空', 500);
    } else if (mb_strlen($extent) > 10000) {
        $response = json('内容过多', 500);
    } else {
        $response = urlToShort($url, $encrypt_type, $extent);
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
