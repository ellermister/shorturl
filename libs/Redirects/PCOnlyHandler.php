<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 2:41
 */

namespace Libs\Redirects;


use Libs\EncryptTool;

class PCOnlyHandler extends Handler
{
    function getHandlerName(): string
    {
        return 'pc_only';
    }

    function requireAuthorize(): bool
    {
        return true;
    }

    function isAuthorize(): bool
    {
        if(isset($_GET['client'])){
            $client = $this->request->getClient();
            if($client){
                if($client->outerWidth() >= 1920 && $client->outerHeight() >= 1024){
                    return  true;
                }
                if(strtolower($client->platform()) == 'win32' && stripos($client->appVersion(), 'windows') !==false){
                    return  true;
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