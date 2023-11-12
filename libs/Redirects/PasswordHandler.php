<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 2:19
 */

namespace Libs\Redirects;


class PasswordHandler extends Handler
{
    function getHandlerName(): string
    {
        return "password";
    }

    function isAuthorize(): bool
    {
        if (isset($_REQUEST['pass'])) {
            if($_REQUEST['pass'] == $this->data['extend']['password']){
                return true;
            }
        }
        view('password');
        return false;
    }

    function showPage(): bool
    {
        return false;
    }

    function requireAuthorize(): bool
    {
        return true;
    }
}