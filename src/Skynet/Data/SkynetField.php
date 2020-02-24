<?php

/**
 * Skynet/Data/SkynetField.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Data;

use Skynet\Encryptor\SkynetEncryptorsFactory;
use SkynetUser\SkynetConfig;

/**
 * Skynet Field
 *
 * Internal datastructure of data with pairs key = value storing in response and request objects
 */
class SkynetField
{
    /** @var string Field name/key */
    private $name;

    /** @var string Field value */
    private $value;

    /** @var SkynetEncryptorInterface[] SkynetEncryptors registry */
    private $encryptorsRegistry = [];

    /** @var SkynetEncryptorInterface SkynetEncryptor instance */
    private $encryptor;

    /** @var bool|null Encryption status of value in field, null - none, false - decrypted, true - encrypted */
    private $isEncrypted = null;

    /**
     * Skynet Exception
     *
     * Operates on exceptions
     *
     * @param string|null $name Field name
     * @param mixed|null $value Field value
     * @param bool $encrypted Status of encryption
     */
    public function __construct($name = null, $value = null, $encrypted = null)
    {
        $this->encryptor = SkynetEncryptorsFactory::getInstance()->getEncryptor();
        $this->isEncrypted = $encrypted;

        $this->setName($name);
        $this->setValue($value);
    }

    /**
     * Encrypts data with encryptor
     *
     * @param string $str String to be encrypted
     * @return string Encrypted data
     */
    private function encrypt($str)
    {
        return $this->encryptor->encrypt($str);
    }

    /**
     * Decrypts data with encryptor
     *
     * @param string $str String to be decrypted
     * @return string Decrypted data
     */
    private function decrypt($str)
    {
        return $this->encryptor->decrypt($str);
    }

    /**
     * Returns field name/key
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns field value
     *
     * Depends on status (encrypted/decrypted) it returns value in states ways, encrypting or decrypting value "on fly"
     *
     * @return string
     */
    public function getValue()
    {
        if (SkynetConfig::get('core_raw') || $this->isEncrypted === null) {
            return $this->value;
        } elseif ($this->isEncrypted === false) {
            return $this->encrypt($this->value);
        } else {
            return $this->decrypt($this->value);
        }
    }

    /**
     * Sets field name/key
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets field value
     *
     * Depends on status (encrypted/decrypted) it sets value in different states, encrypting or decrypting value "on fly"
     *
     * @param string $value
     */
    public function setValue($value)
    {
        if (SkynetConfig::get('core_raw') || $this->isEncrypted === null) {
            $this->value = $value;
        } elseif ($this->isEncrypted === true) {
            $this->value = $this->decrypt($value);
        } else {
            $this->value = $this->encrypt($value);
        }
    }

    /**
     * Sets encryption status
     *
     * Sets information about status of value: FALSE (decrypted), NULL (raw), TRUE (encrypted)
     *
     * @param bool|null $status
     */
    public function setIsEncrypted($status)
    {
        $this->isEncrypted = $status;
    }

    /**
     * Returns encryption status
     *
     * @return bool|null
     */
    public function getIsEncrypted()
    {
        return $this->isEncrypted;
    }

    /**
     * Generates string value of field
     *
     * @return string
     */
    public function __toString()
    {
        return '<b>' . $this->name . '</b>: ' . $this->value;
    }
}