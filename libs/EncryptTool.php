<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2023/11/13
 * Time: 20:00
 */

namespace Libs;


class EncryptTool
{

    /**
     * 兼容UTF8字符并与JS加密解密通用的方法
     *
     * key 不支持utf8字符
     *
     * @param $key
     * @param $plaintext
     * @return string
     */
    public static function encrypt($key, $plaintext): string
    {
        $cypherText = [];
        // 转换为十六进制以正确处理UTF8
        $plaintext = implode('', array_map(function ($c) {
            if (ord($c) < 128) {
                return dechex(ord($c));
            } else {
                return bin2hex($c);
//                return strtolower(rawurlencode($c));
            }
        }, preg_split('//u', $plaintext, -1, PREG_SPLIT_NO_EMPTY)));


        // 将每个十六进制转换为十进制
        $plaintext = array_map('hexdec', str_split($plaintext, 2));

        // 执行异或运算
        for ($i = 0; $i < count($plaintext); $i++) {
            $cypherText[] = $plaintext[$i] ^ ord($key[($i % strlen($key))]);
        }

        // 转换为十六进制, pad 2
        $cypherText = array_map(function ($x) {
            return sprintf("%02X", $x);
//            return dechex($x);
        }, $cypherText);
        return implode('', $cypherText);
    }

    static function decrypt($key, $cypherText)
    {
        try {
            // 将十六进制字符串转换为整数数组
            $cypherText = array_map('hexdec', str_split($cypherText, 2));

            $plaintext = [];

            for ($i = 0; $i < count($cypherText); $i++) {
                // 进行异或运算，并转换为十六进制字符串
                $plaintext[] = dechex($cypherText[$i] ^ ord($key[($i % strlen($key))]));
            }

            // 将十六进制字符串转换为URL编码的字符串
            $decodedPlaintext = '%' . implode('%', $plaintext);
            // 将URL编码的字符串解码为原始字符串
            return rawurldecode($decodedPlaintext);
        } catch (\Exception $e) {
            return false;
        }
    }


    const ENCRYPT_METHOD = 'AES-128-CBC';


    public static function createEncryptDataForURLSafe(array $data): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $passphrase = substr(md5(ENCRYPT_PASSPHRASE), 0, 16);
        $raw = serialize($data);
        $encrypted = openssl_encrypt($raw, self::ENCRYPT_METHOD, $passphrase, 0, $iv);

        return base64_encode_url(sprintf('%s,%s', $iv, $encrypted));
    }

    public static function parseEncryptDataForURLSafe($data)
    {
        $passphrase = substr(md5(ENCRYPT_PASSPHRASE), 0, 16);
        $data = base64_decode_url($data);

        $iv = substr($data, 0, 16);

        $plain = openssl_decrypt(substr($data, 16), self::ENCRYPT_METHOD, $passphrase, 0, $iv);
        return unserialize($plain);
    }

    public static function initGuestPass()
    {
        session_start();
        return $_SESSION['guest_pass'] = getRandStr(32);
    }

    public static function getGuestPass()
    {
        session_start();
        return $_SESSION['guest_pass'] ?? null;
    }

}
