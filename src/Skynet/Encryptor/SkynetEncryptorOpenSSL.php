<?php

/**
 * Skynet/Encryptor/SkynetEncryptorOpenSSL.php
 *
 * @package Skynet
 * @version 1.1.5
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.1.5
 */

namespace Skynet\Encryptor;

use SkynetUser\SkynetConfig;

/**
 * Skynet Encryptor - OpenSSL
 *
 * Simple encryptor uses OpenSSL to encrypt and decrypt sending data
 */
class SkynetEncryptorOpenSSL implements SkynetEncryptorInterface
{
    /**
     * Encrypts data
     *
     * @param string $str Data to encrypt
     *
     * @return string Encrypted data
     */
    public static function encrypt($decrypted)
    {
        $key = md5(SkynetConfig::KEY_ID);
        $iv = openssl_random_pseudo_bytes(16);
        $iv_base64 = base64_encode($iv);
        $encryptedData = @openssl_encrypt($decrypted, SkynetConfig::get('core_encryptor_algorithm'), $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        return base64_encode($iv_base64 . '$:::$' . base64_encode($encryptedData));
    }

    /**
     * Decrypts data
     *
     * @param string $str Data to decrypt
     *
     * @return string Decrypted data
     */
    public static function decrypt($encrypted)
    {
        $key = md5(SkynetConfig::KEY_ID);
        $encrypted = base64_decode($encrypted);
        $parts = explode('$:::$', $encrypted);
        if (count($parts) == 2) {
            $iv = base64_decode($parts[0]);
            $data = base64_decode($parts[1]);
            $decryptedData = @openssl_encrypt($data, SkynetConfig::get('core_encryptor_algorithm'), $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
            return $decryptedData;
        } else {
            return $encrypted;
        }
    }
}