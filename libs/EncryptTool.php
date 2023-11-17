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
     * Compatibility method for handling UTF-8 characters and common encryption/decryption in JavaScript.
     *
     * Note: The key does not support UTF-8 characters.
     *
     * @param string $key
     * @param string $plaintext
     * @return string
     */
    public static function encrypt($key, $plaintext): string
    {
        $cypherText = [];
        // Convert to hexadecimal to correctly handle UTF-8
        $plaintext = implode('', array_map(function ($c) {
            if (ord($c) < 128) {
                return dechex(ord($c));
            } else {
                return bin2hex($c);
//            return strtolower(rawurlencode($c));
            }
        }, preg_split('//u', $plaintext, -1, PREG_SPLIT_NO_EMPTY)));

        // Convert each hexadecimal to decimal
        $plaintext = array_map('hexdec', str_split($plaintext, 2));

        // Perform XOR operation
        for ($i = 0; $i < count($plaintext); $i++) {
            $cypherText[] = $plaintext[$i] ^ ord($key[($i % strlen($key))]);
        }

        // Convert to hexadecimal, pad 2
        $cypherText = array_map(function ($x) {
            return sprintf("%02X", $x);
//            return dechex($x);
        }, $cypherText);
        return implode('', $cypherText);
    }

    /**
     * Decrypts the given ciphertext using the specified key.
     *
     * @param string $key
     * @param string $cypherText
     * @return string|bool The decrypted plaintext or false on failure.
     */
    static function decrypt($key, $cypherText)
    {
        try {
            // Convert hexadecimal string to an array of integers
            $cypherText = array_map('hexdec', str_split($cypherText, 2));

            $plaintext = [];

            for ($i = 0; $i < count($cypherText); $i++) {
                // Perform XOR operation and convert to hexadecimal string
                $plaintext[] = dechex($cypherText[$i] ^ ord($key[($i % strlen($key))]));
            }

            // Convert hexadecimal string to URL-encoded string
            $decodedPlaintext = '%' . implode('%', $plaintext);
            // Decode URL-encoded string to the original plaintext
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
