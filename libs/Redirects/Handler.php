<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 22:23
 */

namespace Libs\Redirects;


/**
 * 处理器内部不要使用 exit 之类中断执行，着重在 showPage 体内不能中断，因为外部还会有一些事件的落地
 *
 * Class Handler
 * @package Libs\Redirects
 */
abstract class Handler
{

    const EVENT_FIRST = 'EVENT_FIRST';
    const EVENT_SHOW_PAGE = 'EVENT_SHOW_PAGE';
    const EVENT_DONE = 'EVENT_DONE';
    const EVENT_EXCEPTION_PAGE = 'EVENT_EXCEPTION_PAGE';

    protected $data = [];

    protected $request;

    public function __construct(array $data, RedirectRequest $request)
    {
        $this->data = $data;
        $this->request = $request;
    }

    abstract function getHandlerName(): string;

    /**
     * 用于外部判断验证是否需要授权
     * @return bool
     */
    abstract function requireAuthorize(): bool;

    /**
     * 内部执行返回是否通过授权的结果
     * @return bool
     */
    abstract function isAuthorize(): bool;

    /**
     * 展示最终的页面或者最终跳转的函数执行体
     * @return bool
     */
    abstract function showPage(): bool;

    /**
     * 完成事件，不做抽象要求，可继承重写
     * 仅对于showPage完成时生效
     */
    public function done()
    {

    }


    /**
     * 异常页面，如伪装页面使用
     */
    public function exceptionPage() :bool
    {

        return false;
    }


    /**
     * @param string $event
     * @param RedirectRequest $request
     * @return bool isBlock isHandle
     */
    function notify(string $event, RedirectRequest $request): bool
    {
        if (!$request->hasTypes($this->getHandlerName())) {
            return false;
        }

        switch ($event){
            case self::EVENT_FIRST:
                // 如果需要授权，并且没有获得授权则进行拦截
                if ($this->requireAuthorize() && !$this->isAuthorize()) {
                    return true;
                }
                break;
            case self::EVENT_SHOW_PAGE:
                return $this->showPage();
                break;
            case self::EVENT_DONE:
                $this->done();
                break;
            case self::EVENT_EXCEPTION_PAGE:
                error_log(sprintf('exception.handler>%s',get_class($this)));
                return $this->exceptionPage();
                break;
        }

        return false;
    }

}