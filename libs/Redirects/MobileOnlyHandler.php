<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 2:41
 */

namespace Libs\Redirects;


use Libs\EncryptTool;

class MobileOnlyHandler extends Handler
{
    function getHandlerName(): string
    {
        return 'mobile_only';
    }

    function requireAuthorize(): bool
    {
        return true;
    }

    function isAuthorize(): bool
    {
        if (isset($_GET['client'])) {
            $client = $this->request->getClient();
            if ($client) {
                // https://uiiiuiii.com/screen/ 手机设备屏幕尺寸
                if ($client->outerWidth() <= 1284 && $client->outerHeight() <= 2778 && $client->outerHeight() > $client->outerWidth()) {
                    return true;
                }
                if (strtolower($client->platform()) == 'iphone' && stripos($client->appVersion(), 'windows') !== false) {
                    return true;
                }

                throw new MessageException(__('The link can only be accessed via mobile devices'));
            }
        }
        view('client');
        return false;
    }

    function showPage(): bool
    {
        return false;
    }

}