<?php

/**
 * Skynet/Cluster/SkynetCluster.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Cluster;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\SkynetVersion;
use Skynet\Data\SkynetRequest;
use Skynet\Data\SkynetResponse;
use Skynet\Common\SkynetHelper;

/**
 * Skynet Cluster Data
 *
 * Stores informations about cluster
 */
class SkynetCluster
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var integer ID in database */
    private $id;

    /** @var string SkynetID of cluster */
    private $skynet_id;

    /** @var string Cluster URL */
    private $url;

    /** @var integer Last connection with cluster */
    private $last_connect;

    /** @var integer Actual chain value */
    private $chain;

    /** @var string Cluster IP */
    private $ip;

    /** @var string Cluter version */
    private $version;

    /** @var string Cluster registrator (another cluster witch sends data) */
    private $registrator;

    /** @var SkynetClusterHeader Cluster header */
    private $header;

    public $test;


    /**
     * Constructor
     */
    public function __construct()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->ip = $_SERVER['SERVER_ADDR'];
        }
        $this->last_connect = time();
        $this->header = new SkynetClusterHeader();
    }

    /**
     * Sets header connection ID if exists
     */
    private function setHeaderStateId()
    {
        if ($this->stateId !== null) {
            $this->header->setStateId($this->stateId);
        }
    }

    /**
     * Connects to remote cluster and get it's header
     *
     * @return string Raw remote data
     */
    public function fromConnect()
    {
        if ($this->url !== null) {
            $this->setHeaderStateId();
            return $this->header->fromConnect($this->url);
        }
    }

    /**
     * Gets remote cluster header saved in responce object
     *
     * @param SkynetResponse $response Response instance
     */
    public function fromResponse(SkynetResponse $response)
    {
        if ($response !== null) {
            $this->setHeaderStateId();
            $this->header->fromResponse($response);
        }
    }

    /**
     * Gets remote cluster header saved in request object
     *
     * @param SkynetRequest $request Request instance
     */
    public function fromRequest(SkynetRequest $request)
    {
        if ($request !== null) {
            $this->setHeaderStateId();
            $this->header->fromRequest($request);
        }
    }

    /**
     * Sets skynet ID
     *
     * @param integer $id SkynetID
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets skynet ID
     *
     * @param integer $id SkynetID
     */
    public function setSkynetId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets cluster URL
     *
     * @param integer $url Cluster URL
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->header->setUrl($url);
    }

    /**
     * Sets time of last connection
     *
     * @param integer $last_connect Unix time format
     */
    public function setLastConnect($last_connect)
    {
        $this->last_connect = $last_connect;
    }

    /**
     * Sets Chain value
     *
     * @param integer $chain Chain value
     */
    public function setChain($chain)
    {
        $this->chain = $chain;
    }

    /**
     * Sets ip address
     *
     * @param string $ip IP Address
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Sets skynet's version
     *
     * @param string $version Version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Sets registrator (other cluster witch was send info about this cluster
     *
     * @param string $registrator Cluster's URL
     */
    public function setRegistrator($registrator)
    {
        $this->registrator = $registrator;
    }

    /**
     * Sets cluster header
     *
     * @param SkynetClusterHeader $header Cluster Header
     */
    public function setHeader(SkynetClusterHeader $header)
    {
        $this->header = $header;
    }

    /**
     * Returns ID in database
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns skynet ID/key
     *
     * @return string
     */
    public function getSkynetId()
    {
        return $this->id;
    }

    /**
     * Returns cluster's URL
     *
     * @return string
     */
    public function getUrl()
    {
        return SkynetHelper::cleanUrl($this->url);
    }

    /**
     * Returns time of latest update in database
     *
     * @return integer Unix time format
     */
    public function getLastConnect()
    {
        return $this->last_connect;
    }

    /**
     * Returns actual chain value
     *
     * @return integer
     */
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * Returns cluster's IP
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Returns skynet's version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns registrator cluster URL
     *
     * @return string
     */
    public function getRegistrator()
    {
        return $this->registrator;
    }

    /**
     * Returns cluster header
     *
     * @return SkynetClusterHeader
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Returns cluster header (alias)
     *
     * @return SkynetClusterHeader
     */
    public function header()
    {
        return $this->header;
    }
}