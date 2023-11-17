<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 1:55
 */

namespace Libs\Redirects;


use Libs\EncryptTool;

/**
 * Class RedirectHandler
 *
 * @package Libs
 */
class RedirectRequest
{

    protected $url;
    protected $hash;
    /**
     * @var array normal,encrypt,password
     */
    protected $encryptTypes = [];
    protected $extend = [];


    /**
     * @var array
     */
    protected $handlers = [];

    public function __construct(string $url, array $encryptTypes, string $hash, array $extend)
    {
        $this->url = $url;
        $this->encryptTypes = $encryptTypes;
        $this->hash = $hash;
        $this->extend = $extend;

        // 优先级
        $this->handlers = [
            $this->makeHandler(BanChinaBrowser::class),
            $this->makeHandler(WhisperHandler::class),
            $this->makeHandler(EncryptHandler::class),
            $this->makeHandler(PasswordHandler::class),
            $this->makeHandler(PCOnlyHandler::class),
            $this->makeHandler(MobileOnlyHandler::class),
            $this->makeHandler(NormalHandler::class),
            $this->makeHandler(OnceHandler::class),
            $this->makeHandler(FakePageHandler::class),
            $this->makeHandler(DynamicHandler::class),
        ];
    }

    /**
     * @param string $class
     * @return Handler
     */
    function makeHandler(string $class): Handler
    {
        return new $class(array(
            'url'          => $this->url,
            'hash'         => $this->hash,
            'encryptTypes' => $this->encryptTypes,
            'extend'       => $this->extend,
        ), $this);
    }


    public function hasTypes(string $name): bool
    {
        return in_array($name, $this->encryptTypes);
    }

    protected function notify($event): bool
    {
        foreach ($this->handlers as $handler) {
            if ($this->hasTypes($handler->getHandlerName())) {
                if ($handler->notify($event, $this)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function handle()
    {

        try {
            if ($this->notify(Handler::EVENT_FIRST)) {
                // 被拦截，则终止
                exit(0);
            }


            if ($this->notify(Handler::EVENT_SHOW_PAGE)) {
                // 渲染完成再去通知完成
                $this->notify(Handler::EVENT_DONE);
            }

        } catch (MessageException $exception) {
            // write log
            error_log(sprintf('%s: %s', $exception->getFile(), $exception->getMessage()));

            // 这个回调不允许报错
            if (!$this->notify(Handler::EVENT_EXCEPTION_PAGE)) {

                // 如果没有处理器处理，则直接报错
                throw $exception;
            }
        }
    }


    public function getClientIP(): string
    {
        $clientIP = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIP = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $clientIP;
    }


    public function getClient()
    {
        if (isset($_GET['client'])) {
            $guestPass = EncryptTool::getGuestPass();
            if ($guestPass) {
                $clientData = EncryptTool::decrypt($guestPass, $_GET['client']);
            } else {
                $clientData = urldecode($_GET['client']);

            }
            $result = explode('|||', $clientData);
            if (count($result) !== 5) {
                error_log(sprintf('客户端请求被修改:' . $clientData));
                return null;
            }
            list($appVersion, $appName, $platform, $outerWidth, $outerHeight) = $result;
            $data = [
                'appVersion'  => $appVersion,
                'appName'     => $appName,
                'platform'    => $platform,
                'outerWidth'  => $outerWidth,
                'outerHeight' => $outerHeight,
            ];

            return new class($data) implements UserClient {
                protected $data = [];

                public function __construct(array $data)
                {
                    $this->data = $data;
                }

                public function appVersion()
                {
                    return $this->data['appVersion'];
                }

                public function appName()
                {
                    return $this->data['appName'];
                }

                public function platform()
                {
                    return $this->data['platform'];
                }

                public function outerWidth()
                {
                    return $this->data['outerWidth'];
                }

                public function outerHeight()
                {
                    return $this->data['outerHeight'];
                }

            };
        }
        return null;
    }
}