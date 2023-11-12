<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 3:59
 */

namespace Libs\Redirects;


use Libs\ShortURL;

class OnceHandler extends Handler
{
    function getHandlerName(): string
    {
        return "once";
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

    public function done()
    {
        $su = new ShortURL();
        $su->removeHash($this->data['hash']);
    }


}