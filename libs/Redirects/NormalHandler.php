<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 3:38
 */

namespace Libs\Redirects;


class NormalHandler extends Handler
{
    function getHandlerName(): string
    {
       return "normal";
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
        header('Location: '.$this->data['url']);
        return true;
    }

}