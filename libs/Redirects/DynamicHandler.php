<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 03:47
 */

namespace Libs\Redirects;


/**
 * Class DynamicHandler
 * 所谓动态方式就是 no referer
 * @package Libs\Redirects
 */
class DynamicHandler extends Handler
{
    function getHandlerName(): string
    {
        return "dynamic";
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
        view('dynamic', [
            'url' => $this->data['url'],
        ]);
        return true;
    }

}