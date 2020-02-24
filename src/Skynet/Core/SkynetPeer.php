<?php

/**
 * Skynet/Core/SkynetPeer.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Core;

use Skynet\Connection\SkynetConnectionsFactory;
use Skynet\Data\SkynetRequest;
use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use SkynetUser\SkynetConfig;

/**
 * Skynet Peer
 *
 * Allows to sending Skynet requests inside another Skynet
 */
class SkynetPeer
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var SkynetRequest Request to send */
    private $request;

    /** @var SkynetConnectionInterface Connector instance */
    private $connection;

    /** @var string Received raw data */
    private $receivedData;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connection = SkynetConnectionsFactory::getInstance()->getConnector(SkynetConfig::get('core_connection_type'));
        $this->request = new SkynetRequest();
        $this->request->addMetaData();
    }

    /**
     * Connects to another Skynet
     *
     * @param string $address Cluster address
     *
     * @return string Received data
     */
    public function connect($address)
    {
        $this->request->set('@peer', 1);
        $this->connection->assignRequest($this->request);
        $this->receivedData = $this->connection->connect($address);
        return $this->receivedData['data'];
    }

    /**
     * Assigns request to send
     *
     * @param SkynetRequest $request
     */
    public function assignRequest(SkynetRequest $request)
    {
        $this->request = clone $request;
    }

    /**
     * Returns request
     *
     * @return SkynetRequest Request instance
     */
    public function getRequest()
    {
        return $this->request;
    }
}