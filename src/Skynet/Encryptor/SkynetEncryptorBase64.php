<?php

/**
 * Skynet/Encryptor/SkynetEncryptorBase64.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Encryptor;

/**
 * Skynet Encryptor - base64
 *
 * Simple encryptor uses base64 to encrypt and decrypt sending data
 */
class SkynetEncryptorBase64 implements SkynetEncryptorInterface
{
    /**
     * Encrypts data
     *
     * @param string $str Data to encrypt
     *
     * @return string Encrypted data
     */
    public static function encrypt($str)
    {
        return base64_encode($str);
    }

    /**
     * Decrypts data
     *
     * @param string $str Data to decrypt
     *
     * @return string Decrypted data
     */
    public static function decrypt($str)
    {
        return base64_decode($str);
    }
}