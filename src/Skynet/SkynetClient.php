<?php

/**
 * Skynet/SkynetClient.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.2.0
 */

namespace Skynet;

use Skynet\Core\SkynetConnect;
use Skynet\Core\SkynetOutput;
use Skynet\Data\SkynetRequest;
use Skynet\Data\SkynetResponse;
use Skynet\Cluster\SkynetClustersRegistry;
use Skynet\Cluster\SkynetCluster;
use Skynet\Secure\SkynetVerifier;

/**
 * Skynet Client
 */
class SkynetClient
{
    /** @var SkynetResponse Assigned response */
    public $response;

    /** @var SkynetRequest Assigned request */
    public $request;

    /** @var SkynetClustersRegistry ClustersRegistry instance */
    public $clustersRegistry;

    /** @var SkynetVerifier Verifier instance */
    public $verifier;

    /** @var bool Status od connection with cluster */
    public $isConnected = false;

    /**
     * Constructor
     *
     * @return SkynetClient This instance
     */
    public function __construct()
    {
        $this->request = new SkynetRequest(true);
        $this->response = new SkynetResponse(true);
        $this->clustersRegistry = new SkynetClustersRegistry(true);
        $this->verifier = new SkynetVerifier();
        return $this;
    }

    /**
     * Connects to single skynet cluster via URL
     *
     * @param string|SkynetCluster $remote_cluster URL to remote skynet cluster, e.g. http://server.com/skynet.php, default: NULL
     * @param integer $chain Forces new connection chain value, default: NULL
     *
     * @return Skynet $this Instance of this
     */
    public function connect($cluster = null, $chain = null)
    {
        $this->request->set('_skynet_client', 1);
        $connect = new SkynetConnect(true);
        $connect->assignRequest($this->request);
        $connect->assignResponse($this->response);
        $connect->assignConnectId(1);
        $connect->setIsBroadcast(false);

        try {
            $connResult = $connect->connect($cluster, $chain);
            if ($connResult) {
                $this->isConnected = true;
            }

        } catch (SkynetException $e) {
            $this->addError('Connection error: ' . $e->getMessage(), $e);
        }
        return $this;
    }
}