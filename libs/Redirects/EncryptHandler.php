<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 22:25
 */

namespace Libs\Redirects;


use Libs\EncryptTool;

class EncryptHandler extends Handler
{


    function isAuthorize(): bool
    {
        return false;
    }

    function showPage(): bool
    {
        if (isset($_GET['a'])) {
            $payload = file_get_contents("php://input");
            $data = EncryptTool::parseEncryptDataForURLSafe($payload);

            if ((time() - intval($data['timestamp'])) > 30) {
                echo "1" . makeMessageJs(__('The link has expired'));
            } else {
                echo "0" . EncryptTool::encrypt($data['pass'], makeRedirectJs($data['url']));
            }

            return true;
        } else {
            // 第一步，封装一个 token 给POST请求使用,
            // 这个 token 只能使用一次，并且存在时间校验
            $pass = getRandStr(16); // 客户端自己解密时需要的KEY

            $cipher = EncryptTool::createEncryptDataForURLSafe([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'timestamp'  => time(),
                'url'        => $this->data['url'],
                'pass'       => $pass,
            ]);
            $render['encrypt_data'] = sprintf('%s,%s', $pass, $cipher);

            view('encrypt', $render);
        }
        return false;
    }

    function getHandlerName(): string
    {
        return "encrypt";
    }

    function requireAuthorize(): bool
    {
        return false;
    }
}