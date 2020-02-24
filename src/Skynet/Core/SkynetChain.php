<?php

/**
 * Skynet/Core/SkynetChain.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Core;

use PDO;
use PDOException;
use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\Encryptor\SkynetEncryptorsFactory;
use Skynet\Secure\SkynetVerifier;
use Skynet\Database\SkynetDatabase;
use Skynet\Common\SkynetTypes;
use Skynet\Common\SkynetHelper;
use Skynet\Cluster\SkynetClustersRegistry;
use Skynet\SkynetVersion;
use SkynetUser\SkynetConfig;

/**
 * Skynet Chain Value
 *
 * Stores identifier of current clusters state
 */
class SkynetChain
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var SkynetDatabase PDO connection instance */
    protected $db;

    /** @var bool Status of database connection */
    protected $dbConnected = false;

    /** @var bool Status of tables schema */
    protected $dbCreated = false;

    /** @var SkynetEncryptorInterface Encryptor instance */
    private $encryptor;

    /** @var SkynetVerifier Verifier instance */
    private $verifier;

    /** @var SkynetClustersRegistry ClustersRegistry instance */
    private $clustersRegistry;

    /** @var integer Actua chain value */
    private $chain;

    /** @var integer Time of last chain update */
    private $updated_at;

    /** @var bool True if connection from Client */
    private $isClient = false;

    /**
     * Constructor
     *
     * @param bool $isClient True if Client
     */
    public function __construct($isClient = false)
    {
        $this->isClient = $isClient;
        $dbInstance = SkynetDatabase::getInstance();
        $this->db_connected = $dbInstance->isDbConnected();
        $this->db_created = $dbInstance->isDbCreated();
        $this->db = $dbInstance->getDB();
        $this->encryptor = SkynetEncryptorsFactory::getInstance()->getEncryptor();
        $this->verifier = new SkynetVerifier();
        $this->clustersRegistry = new SkynetClustersRegistry($isClient);

        $this->updateMyChain();
        $this->showMyChain();
    }

    /**
     * Checks for reqest for show my chain
     *
     * @return bool True if is request for chain
     */
    public function isRequestForChain()
    {
        if (isset($_REQUEST['_skynet_chain_request'])) {
            return true;
        }
    }

    /**
     * Encrypts data with encryptor
     *
     * @param string $str String to be encrypted
     * @return string Encrypted data
     */
    private function encrypt($str)
    {
        if (!SkynetConfig::get('core_raw')) {
            return $this->encryptor->encrypt($str);
        } else {
            return $str;
        }
    }

    /**
     * Decrypts data with encryptor
     *
     * @param string $str String to be decrypted
     * @return string Decrypted data
     */
    private function decrypt($str)
    {
        if (!SkynetConfig::get('core_raw')) {
            return $this->encryptor->decrypt($str);
        } else {
            return $str;
        }
    }

    /**
     * Generates hash
     */
    private function addHash()
    {
        $hash = sha1(SkynetHelper::getMyUrl() . SkynetConfig::KEY_ID);
        return $hash;
    }

    /**
     * Shows cluster chain value and header as encoded JSON
     */
    public function showMyChain()
    {
        $ary = [];

        if ($this->isRequestForChain()) {
            if (!$this->verifier->isRequestKeyVerified()) {
                exit;
            }

            if ($this->isChain()) {
                $this->loadChain();
                $ary['_skynet_chain'] = $this->encrypt($this->chain);
                $ary['_skynet_chain_updated_at'] = $this->encrypt($this->updated_at);
            } else {
                $ary['_skynet_chain'] = $this->encrypt('0');
                $ary['_skynet_chain_updated_at'] = $this->encrypt('0');
            }

            $ary['_skynet_hash'] = $this->encrypt($this->verifier->generateHash());

            /* Add header data */
            $ary['_skynet_id'] = $this->encrypt(SkynetConfig::KEY_ID);
            $ary['_skynet_cluster_url'] = $this->encrypt(SkynetHelper::getMyUrl());
            $ary['_skynet_cluster_ip'] = $this->encrypt($_SERVER['REMOTE_ADDR']);
            $ary['_skynet_version'] = $this->encrypt(SkynetVersion::VERSION);
            $ary['_skynet_clusters_chain'] = $this->encrypt($this->clustersRegistry->parseMyClusters());

            echo json_encode($ary);

            /* Stop execution when chain is rendered */
            exit;
        }
    }

    /**
     * Increments chain value and saves it in database
     *
     * @return bool True if success
     */
    public function newChain()
    {
        if (isset($_REQUEST['_skynet_cluster_url'])) {
            return false;
        }
        $this->loadChain();
        $nextChain = $this->chain + 1;
        if ($this->updateChain($nextChain)) {
            return true;
        }
    }

    /**
     * Checks for chain update request and updates chain in database if request for update exists
     *
     * @return bool True if success
     */
    public function updateMyChain()
    {
        if (isset($_REQUEST['_skynet_chain_new'])) {
            if (!$this->verifier->isRequestKeyVerified()) {
                exit;
            }
            $newChain = intval($this->decrypt($_REQUEST['_skynet_chain_new']));
            $this->loadChain();

            if ($newChain > $this->chain) {
                $this->updateChain($newChain);
                return true;
            }
        }
    }

    /**
     * Checks for chain data in database
     *
     * @return bool True if success
     */
    public function isChain()
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT chain FROM skynet_chain WHERE id = 1');
            $stmt->execute();
            $result = $stmt->fetchColumn();
            $stmt->closeCursor();
            if ($result > 0) {
                return true;
            }

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::CHAIN, SkynetTypes::DBCONN_ERR . ' : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }


    /**
     * Creates chain in DB
     *
     * @return bool
     */
    public function createChain()
    {
        try {
            $stmt = $this->db->query(
                'INSERT INTO skynet_chain (id, chain, updated_at) VALUES(1, 0, 0)');
            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::CHAIN, 'INSERT CHAIN ERROR : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }


    /**
     * Loads and returns actual chain data from database
     *
     * @return string[] Row with chain data
     */
    public function loadChain()
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT chain, updated_at FROM skynet_chain WHERE id = 1');
            $stmt->execute();
            $row = $stmt->fetch();
            $stmt->closeCursor();
            $this->chain = $row['chain'];
            $this->updated_at = $row['updated_at'];
            return $row;

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::CHAIN, SkynetTypes::DBCONN_ERR . ' : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Updates chain in database
     *
     * @param integer $chain New chain value
     * @return bool True if success
     */
    public function updateChain($chain = null)
    {
        try {
            if ($chain === null) {
                $chain = $this->chain;
            }
            $time = time();
            $stmt = $this->db->prepare(
                'UPDATE skynet_chain SET chain = :chain, updated_at = :time WHERE id = 1');
            $stmt->bindParam(':chain', $chain, PDO::PARAM_INT);
            $stmt->bindParam(':time', $time, PDO::PARAM_INT);
            if ($stmt->execute()) {
                return true;
            }

        } catch (PDOException $e) {
            $this->addState(SkynetTypes::CHAIN, SkynetTypes::DBCONN_ERR . ' : ' . $e->getMessage());
            $this->addError(SkynetTypes::PDO, 'DB CONNECTION ERROR: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Returns chain value
     *
     * @return integer Chain value
     */
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * Sets chain value
     *
     * @param integer
     */
    public function setChain($chain)
    {
        $this->chain = (int)$chain;
    }

    /**
     * Returns time when chain was updated
     *
     * @return integer Time in unix format
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Sets update time
     *
     * @param integer Time in unix format
     */
    public function setUpdatedAt($time)
    {
        $this->updated_at = $time;
    }
}