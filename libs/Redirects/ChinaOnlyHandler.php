<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 2:41
 */

namespace Libs\Redirects;


use Libs\EncryptTool;

class ChinaOnlyHandler extends Handler
{
    function getHandlerName(): string
    {
        return 'china_only';
    }

    function requireAuthorize(): bool
    {
        return true;
    }

    function isAuthorize(): bool
    {
        $clientIP = $this->request->getClientIP();
        if (!ip_is_china($clientIP)) {
            throw new MessageException(__('Access only to users in mainland China'));
        }
        return false;
    }

    function showPage(): bool
    {
       return false;
    }

}