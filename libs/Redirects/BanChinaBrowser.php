<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/17
 * Time: 23:40
 */

namespace Libs\Redirects;


class BanChinaBrowser extends Handler
{

    const KEYWORD_IN_USERAGENT = [
        'bidubrowser',
        'metasr',
        'tencenttraveler',
        'MicroMessenger',
        'MiuiBrowser',
        'YodaoBot',
        'IqiyiApp',
        'Weibo',
        'qq',
        'QQBrowser',
        'Quark',
        'MetaSr',
        'SNEBUY-APP',
        'AlipayClient',
        'AliApp',
        '115Browser',
        '2345Explorer',
        'Mb2345Browser',
        '2345chrome',
        'QihooBrowser',
        'QHBrowser',
        '360Spider',
        'HaosouSpider',
        'BIDUBrowser',
        'baidubrowser',
        'baiduboxapp',
        'BaiduD',
        'DingTalk',
        'douban.frodo',
        'aweme',
        'HuaweiBrowser',
        'HUAWEI',
        'HONOR',
        'HBPC',
        'LBBROWSER',
        'LieBaoFast',
        'MZBrowser',
        'HeyTapBrowser',
        'OPPO',
        'Opera',
        'VivoBrowser',
    ];

    function getHandlerName(): string
    {
        return "ban_china_browser";
    }

    function requireAuthorize(): bool
    {
        return true;
    }

    function isAuthorize(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userAgent = strtolower($userAgent);
        foreach (self::KEYWORD_IN_USERAGENT as $value) {
            if (strpos($userAgent, strtolower($value)) !== false) {
                view('ban_china_browser', ['url' => $this->data['url']]);
                return false;
            }
        }
        return true;
    }

    function showPage(): bool
    {
        return false;
    }

}