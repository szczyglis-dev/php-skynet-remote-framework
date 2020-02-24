<?php

/**
 * Skynet/Database/SkynetRegistry.php
 *
 * Checking and veryfing access to skynet
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Database;

use PDO;
use PDOException;
use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\Secure\SkynetVerifier;
use Skynet\Common\SkynetTypes;
use SkynetUser\SkynetConfig;

/**
 * Skynet Registry
 */
class SkynetRegistry
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var string Current table in Database view */
    protected $selectedTable;

    /** @var string[] Array with table names */
    protected $dbTables;

    /** @var SkynetDatabase DB Instance */
    protected $database;

    /** @var PDO Connection instance */
    protected $db;

    /** @var string[] Array with tables fields */
    protected $tablesFields = [];

    /** @var SkynetVerifier Verifier instance */
    private $verifier;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->database = SkynetDatabase::getInstance();
        $this->db = $this->database->connect();
        $this->verifier = new SkynetVerifier();
    }

    /**
     * Check for key exists
     *
     * @param string $key Key
     *
     * @return bool
     */
    public function isRegistryKey($key)
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT count(*) as c FROM skynet_registry WHERE key = :key');
            $stmt->bindParam(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch();
            $stmt->closeCursor();
            if ($result['c'] > 0) {
                return true;
            }

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::REGISTRY, SkynetTypes::DBCONN_ERR . ' : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Inserts new record
     *
     * @param string $key Key
     * @param string $value value
     *
     * @return bool
     */
    public function insertRegistryKey($key, $value)
    {
        try {
            $id = SkynetConfig::KEY_ID;
            $time = time();
            $stmt = $this->db->prepare('INSERT INTO skynet_registry (skynet_id, created_at, key, content) VALUES(:skynet_id, :time, :key, :content)');
            $stmt->bindParam(':skynet_id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':time', $time, PDO::PARAM_INT);
            $stmt->bindParam(':key', $key, PDO::PARAM_STR);
            $stmt->bindParam(':content', $value, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $this->addState(SkynetTypes::REGISTRY, 'INSERTED NEW KEY:' . $key);
                return true;
            } else {
                $this->addState(SkynetTypes::REGISTRY, 'INSERT NEW KEY FAILED:' . $key);
            }

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::REGISTRY, SkynetTypes::DBCONN_ERR . ' : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Updates record
     *
     * @param string $key Key
     * @param string $value value
     *
     * @return bool
     */
    public function setRegistryValue($key, $value)
    {
        if (!$this->isRegistryKey($key)) {
            $this->insertRegistryKey($key, $value);
        }

        try {
            $id = SkynetConfig::KEY_ID;
            $time = time();
            $stmt = $this->db->prepare('UPDATE skynet_registry SET created_at = :time, content = :content WHERE key = :key');
            $stmt->bindParam(':time', $time, PDO::PARAM_INT);
            $stmt->bindParam(':content', $value, PDO::PARAM_STR);
            $stmt->bindParam(':key', $key, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $this->addState(SkynetTypes::REGISTRY, 'UPDATED KEY:' . $key);
                return true;
            } else {
                $this->addState(SkynetTypes::REGISTRY, 'UPDATE KEY FAILED:' . $key);
            }

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::REGISTRY, SkynetTypes::DBCONN_ERR . ' : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Gets record value
     *
     * @param string $key Key
     *
     * @return mixed Value
     */
    public function getRegistryValue($key)
    {
        if (!$this->isRegistryKey($key)) {
            $this->insertRegistryKey($key, '');
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }

        $content = '';
        try {
            $stmt = $this->db->prepare(
                'SELECT content FROM skynet_registry WHERE key = :key');
            $stmt->bindParam(':key', $key);
            $stmt->execute();
            $row = $stmt->fetch();
            $content = $row['content'];
            $stmt->closeCursor();

        } catch (PDOException $e) {
            $this->addError(SkynetTypes::PDO, 'Registry: getting record for key [' . $key . '] failed', $e);
            return '';
        }
        return $content;
    }
}