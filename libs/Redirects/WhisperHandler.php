<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 3:43
 */

namespace Libs\Redirects;


class WhisperHandler extends Handler
{
    function getHandlerName(): string
    {
        return "whisper";
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
        view('whisper2', [
            'url'     => $this->data['url'],
            'whisper' => $this->data['extend']['whisper'],
        ]);

        return true;
    }

}