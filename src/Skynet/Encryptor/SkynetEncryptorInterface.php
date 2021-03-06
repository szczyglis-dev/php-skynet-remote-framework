<?php

/**
 * Skynet/Encryptor/SkynetEncryptorInterface.php
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
 * Skynet Encryptor Interface
 *
 * Interface for custom encryption classes. You can use your own algorithms for encrypting data sending and receiving by Skynet.
 * Every encryption class must implements this interface.
 */
interface SkynetEncryptorInterface
{
    /**
     * Encrypts data
     *
     * @param string $str Data to encrypt
     *
     * @return string Encrypted data
     */
    public static function encrypt($str);

    /**
     * Decrypts data
     *
     * @param string $str Data to decrypt
     *
     * @return string Decrypted data
     */
    public static function decrypt($str);
}