<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/14
 * Time: 11:19
 */

namespace Libs;


class Languages
{

    protected static $data = [];
    protected static $lang = '';

    public static function setConfig(array $data = [])
    {
        self::$data = $data;
    }


    public static function setLocale(string $lang)
    {
        self::$lang = $lang;
    }

    public static function trans(string $name, array $_vars = [])
    {
        $locale = self::$lang;
        if (!isset(self::$data[$locale])) {
            $locale = 'en';
        }
        if (!isset(self::$data[$locale][$name])) {
            $locale = 'en';
        }
        $text = self::$data[$locale][$name] ?? $name;
        foreach ($_vars as $name => $value) {
            $text = str_replace(":$name", $value, $text);
        }
        return $text;
    }
}