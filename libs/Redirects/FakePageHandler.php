<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 4:18
 */

namespace Libs\Redirects;


/**
 * Class FakePageHandler
 * 更改性质，仅在认证失败时返回伪装页面
 *
 * @package Libs\Redirects
 */
class FakePageHandler extends Handler
{

    function getHandlerName(): string
    {
        return "fake_page";
    }

    function requireAuthorize(): bool
    {
        return false;
    }

    function isAuthorize(): bool
    {
        return true;
    }

    function showPage(): bool
    {
        return false;
    }


    /**
     * 异常页面，如伪装页面使用
     */
    public function exceptionPage(): bool
    {
        $url = 'https://item.jd.com/10000' . (str_pad(rand(0, 9999999), 7, '0')) . '.html';
        header('Location: '.$url);
        return true;
    }
}