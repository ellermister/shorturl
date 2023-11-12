<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 12:05
 */

namespace Libs\Redirects;


interface UserClient
{

    public function appVersion();
    public function appName();
    public function platform();
    public function outerWidth();
    public function outerHeight();
}