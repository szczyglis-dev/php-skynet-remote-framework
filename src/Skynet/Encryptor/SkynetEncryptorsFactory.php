<?php

/**
 * Skynet/Encryptor/SkynetEncryptorsFactory.php
 *
 * @package Skynet
 * @version 1.1.5
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Encryptor;

use SkynetUser\SkynetConfig;

/**
 * Skynet Encryptors Factory
 *
 * Factory for encryptors. You can register here your own enryption classes
 */
class SkynetEncryptorsFactory
{
    /** @var SkynetEncryptorInterface[] Array of encryptors */
    private $encryptorsRegistry = [];

    /** @var SkynetEncryptorInterface Choosen encryptor instance */
    private $encryptor;

    /** @var SkynetEncryptorsFactory Instance of this */
    private static $instance = null;


    /**
     * Constructor (private)
     */
    private function __construct()
    {
    }

    /**
     * __clone (private)
     */
    private function __clone()
    {
    }

    /**
     * Registers encryptor classes in registry
     */
    private function registerEncryptors()
    {
        $this->register('openSSL', new SkynetEncryptorOpenSSL());
        $this->register('mcrypt', new SkynetEncryptorMcrypt());
        $this->register('base64', new SkynetEncryptorBase64());
    }

    /**
     * Returns choosen encryptor from registry
     *
     * @param string $name
     *
     * @return SkynetEncryptorInterface Encryptor
     */
    public function getEncryptor($name = null)
    {
        if ($name === null) {
            $name = SkynetConfig::get('core_encryptor');
        }
        if (is_array($this->encryptorsRegistry) && array_key_exists($name, $this->encryptorsRegistry)) {
            return $this->encryptorsRegistry[$name];
        }
    }

    /**
     * Registers encyptor in registry
     *
     * @param string $id name/key of encryptor
     * @param SkynetEncryptorInterface $class New instance of encryptor class
     */
    private function register($id, SkynetEncryptorInterface $class)
    {
        $this->encryptorsRegistry[$id] = $class;
    }

    /**
     * Returns instance
     *
     * @return SkynetEncryptorsFactory
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
            self::$instance->registerEncryptors();
        }
        return self::$instance;
    }
}