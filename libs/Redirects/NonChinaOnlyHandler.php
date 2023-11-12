<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 2:41
 */

namespace Libs\Redirects;


use Libs\EncryptTool;

class NonChinaOnlyHandler extends Handler
{
    function getHandlerName(): string
    {
        return 'non_china_only';
    }

    function requireAuthorize(): bool
    {
        return true;
    }

    function isAuthorize(): bool
    {
        $clientIP = $this->request->getClientIP();
        if (ip_is_china($clientIP)) {
            throw new MessageException(__('Only access users who are not in mainland China'));
        }
        return false;
    }

    function showPage(): bool
    {
       return false;
    }

}