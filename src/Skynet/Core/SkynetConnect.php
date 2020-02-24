<?php

/**
 * Skynet/Core/SkynetConnect.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.1.1
 */

namespace Skynet\Core;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\Error\SkynetException;
use Skynet\State\SkynetStatesTrait;
use Skynet\EventListener\SkynetEventListenersLauncher;
use Skynet\Common\SkynetHelper;
use Skynet\Connection\SkynetConnectionsFactory;
use Skynet\Secure\SkynetVerifier;
use Skynet\Core\SkynetChain;
use Skynet\Data\SkynetRequest;
use Skynet\Data\SkynetResponse;
use Skynet\Cluster\SkynetClustersRegistry;
use Skynet\Cluster\SkynetCluster;
use Skynet\Common\SkynetTypes;
use Skynet\Debug\SkynetDebug;
use SkynetUser\SkynetConfig;


/**
 * Skynet Connect
 */
class SkynetConnect
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var SkynetRequest Assigned request */
    private $request;

    /** @var SkynetResponse Assigned response */
    private $response;

    /** @var integer Actual connection number */
    private $connectId = 0;

    /** @var string Cluster URL in actual connection */
    private $clusterUrl;

    /** @var SkynetCli CLI Console */
    private $cli;

    /** @var SkynetConsole HTML Console */
    private $console;

    /** @var SkynetClustersRegistry ClustersRegistry instance */
    private $clustersRegistry;

    /** @var SkynetEventListenersLauncher Listeners Launcher */
    private $eventListenersLauncher;

    /** @var SkynetConnectionInterface Connector instance */
    private $connection;

    /** @var SkynetChain SkynetChain instance */
    private $skynetChain;

    /** @var SkynetVerifier Verifier instance */
    private $verifier;

    /** @var SkynetCluster Actual cluster */
    private $cluster = null;

    /** @var SkynetCluster[] Array of clusters */
    private $clusters = [];

    /** @var bool Status od connection with cluster */
    private $isConnected = false;

    /** @var bool Status of response */
    private $isResponse = false;

    /** @var bool Status of broadcast */
    private $isBroadcast = false;

    /** @var string Raw response from connect() */
    private $responseData;

    /** @var string Raw header response from getHeader() */
    private $responseHeaderData;

    /** @var mixed[] Sended params in header request */
    private $sendedHeaderDataParams;

    /** @var bool Controller for break connections if specified receiver set */
    private $breakConnections = false;

    /** @var string[] Array with connections debug */
    private $connectionData = [];

    /** @var SkynetDebug Debugger */
    private $debugger;

    /** @var bool Execute connection or not */
    private $doConnect;

    /** @var string Monits */
    private $monits = [];

    /** @var bool True if adding new to DB */
    private $addition = false;

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
        $this->eventListenersLauncher = new SkynetEventListenersLauncher();
        $this->eventListenersLauncher->setSender(true);
        $this->eventListenersLauncher->assignRequest($this->request);
        $this->eventListenersLauncher->assignResponse($this->response);
        $this->eventListenersLauncher->assignCli($this->cli);
        $this->eventListenersLauncher->assignConsole($this->console);
        $this->connection = SkynetConnectionsFactory::getInstance()->getConnector(SkynetConfig::get('core_connection_type'));
        $this->verifier = new SkynetVerifier();
        $this->clustersRegistry = new SkynetClustersRegistry($isClient);
        $this->debugger = new SkynetDebug();
    }

    /**
     * Connects to single skynet cluster via URL
     *
     * Method connects to cluster, sends request, gets response and puts cluster URL into database (if not exists yet).
     *
     * @param string|SkynetCluster $remote_cluster URL to remote skynet cluster, e.g. http://server.com/skynet.php, default: NULL
     * @param integer $chain Forces new connection chain value, default: NULL
     *
     * @return Skynet $this Instance of this
     */
    public function connect($remote_cluster = null, $chain = null)
    {
        $result = false;
        $this->doConnect = true;

        $this->init();
        $this->cluster = $this->prepareCluster($remote_cluster);

        if (empty($this->clusterUrl) || $this->clusterUrl === null) {
            return false;
        }

        /* If next connection in broadcast mode */
        if ($this->connectId > 1) {
            $this->newData();
        }

        $this->eventListenersLauncher->assignSenderClusterUrl(SkynetHelper::getMyUrl());
        $this->eventListenersLauncher->assignReceiverClusterUrl($this->cluster->getUrl());
        $this->prepareListeners();

        $this->prepareRequest($chain);

        if (!$this->doConnect) {
            return null;
        }

        $this->responseData = $this->sendRequest();

        if ($this->responseData === null || $this->responseData === false) {
            $this->cluster->getHeader()->setResult(-1);
            throw new SkynetException(SkynetTypes::CONN_ERR);

        } else {

            $this->prepareResponse();
            $this->updateClusterHeader();
            $this->storeCluster();

            $this->eventListenersLauncher->assignSenderClusterUrl($this->cluster->getUrl());
            $this->eventListenersLauncher->assignReceiverClusterUrl(SkynetHelper::getMyUrl());
            $this->launchResponseListeners();
            $result = true;
        }

        $clustersMonits = $this->clustersRegistry->getMonits();
        if (count($clustersMonits) > 0) {
            $this->monits = array_merge($this->monits, $clustersMonits);
        }

        $listenersMonits = $this->eventListenersLauncher->getMonits();
        if (count($listenersMonits) > 0) {
            $this->monits[] = '<strong>Connection (' . $this->connectId . ') ' . $this->clusterUrl . ':</strong>';
            $this->monits = array_merge($this->monits, $listenersMonits);
            $this->monits[] = '';
        }

        $this->saveConnectionData();
        return $this->isConnected;
    }

    /**
     * Returns connection data
     *
     * @return string[] Connection output
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Launches listeners
     */
    private function launchResponseListeners()
    {
        $id = $this->cluster->getHeader()->getId();

        /* Launch response listeners */
        if ($this->cluster->getHeader()->getId() !== null && $this->verifier->isRequestKeyVerified($this->cluster->getHeader()->getId(), true, $this->response)) {
            $this->eventListenersLauncher->launch('onResponse');
            $this->eventListenersLauncher->launch('onResponseLoggers');
        }
    }

    /**
     * Stores cluster in DB
     */
    private function storeCluster()
    {
        if ($this->isClient && !SkynetConfig::get('client_registry')) {
            return false;
        }

        $this->clustersRegistry->setRegistrator(SkynetHelper::getMyUrl());

        /* Add cluster to database if not exists */
        if ($this->isConnected) {
            if ($this->clustersRegistry->isClusterBlocked($this->cluster)) {
                $this->clustersRegistry->removeBlocked($this->cluster);
            }

            if ($this->clustersRegistry->add($this->cluster)) {
                $this->addition = true;
            }
            $this->cluster->getHeader()->setResult(1);
        }
    }

    /**
     * Updates cluster header with connID
     */
    private function updateClusterHeader()
    {
        /* Get header of remote cluster */
        $this->cluster->getHeader()->setStateId($this->connectId);
        $this->cluster->getHeader()->setConnId($this->connectId);
        $this->cluster->fromResponse($this->response);

        /* If single connection via $skynet->connect(CLUSTER_ADDRESS); */
        if (!$this->isBroadcast) {
            $clusterAddress = SkynetHelper::cleanUrl($this->clusterUrl);
            $this->cluster->getHeader()->setUrl($clusterAddress);
        }
    }

    /**
     * Parses response
     */
    private function prepareResponse()
    {
        /* Parse response */
        $this->response->setRawReceivedData($this->responseData);
        if (!empty($this->responseData) && $this->responseData !== false) {
            $this->isResponse = true;
            $this->addState(SkynetTypes::CONN_OK, 'RESPONSE DATA TRANSFER OK: ' . $this->clusterUrl);
        } else {
            $this->addState(SkynetTypes::CONN_OK, '[[ERROR]] RECEIVING RESPONSE: ' . $this->clusterUrl);
        }
        $this->response->parseResponse();
        $responses = $this->response->getResponseData();
    }

    /**
     * Connects and sends request
     *
     * @return string Raw response data
     */
    private function sendRequest()
    {
        $this->isConnected = false;
        $this->connection->assignRequest($this->request);
        $this->adapter = $this->connection->connect();
        $this->responseData = $this->adapter['data'];
        if ($this->adapter['result'] === true) {
            $this->isConnected = true;
        } else {

            if (!$this->isClient || SkynetConfig::get('client_registry')) {
                $this->clustersRegistry->setRegistrator(SkynetHelper::getMyUrl());
                $this->clustersRegistry->addBlocked($this->cluster);
            }

            $this->cluster->getHeader()->setResult(-1);
        }
        return $this->responseData;
    }

    /**
     * Prepares request
     *
     * @param int $chain New chain value
     */
    private function prepareRequest($chain = null)
    {
        /* Prepare request */
        $this->connection->setCluster($this->cluster);
        $this->request->addMetaData($chain);

        $this->doConnect = true;

        /* Try to connect and get response, launch pre-request listeners */
        $this->eventListenersLauncher->launch('onRequest');

        $requests = $this->request->getRequestsData();

        /* If specified receiver via [@to] */
        if (isset($requests['@to'])) {
            $to = $this->request->get('@to');
            $actualUrl = SkynetConfig::get('core_connection_protocol') . $this->clusterUrl;
            if (is_string($to)) {
                if ($this->verifier->isAddressCorrect($to)) {
                    if ($actualUrl != $to) {
                        $this->doConnect = false;
                    }
                }
            } elseif (is_array($to)) {
                if (!in_array($actualUrl, $to)) {
                    $this->doConnect = false;
                }
            }
        }

        $this->eventListenersLauncher->launch('onRequestLoggers');
    }

    /**
     * Assigns data to listeners
     */
    private function prepareListeners()
    {
        $this->eventListenersLauncher->assignRequest($this->request);
        $this->eventListenersLauncher->assignResponse($this->response);
        $this->eventListenersLauncher->assignConnectId($this->connectId);
        $this->eventListenersLauncher->assignClusterUrl($this->clusterUrl);
    }

    /**
     * Prepares cluster object
     *
     * @param SkynetCluster|string $remote_cluster Cluster or address
     *
     * @return SkynetCluster
     */
    private function prepareCluster($remote_cluster = null)
    {
        /* Prepare cluster object and address */
        if ($remote_cluster !== null && !empty($remote_cluster)) {
            if ($remote_cluster instanceof SkynetCluster) {
                $this->cluster = $remote_cluster;
                $this->clusterUrl = $this->cluster->getUrl();

            } elseif (is_string($remote_cluster) && !empty($remote_cluster)) {
                $remote_cluster = SkynetHelper::cleanUrl($remote_cluster);
                $this->cluster = new SkynetCluster();
                $this->cluster->setUrl($remote_cluster);
                $this->clusterUrl = $remote_cluster;
            }
        }
        return $this->cluster;
    }

    /**
     * Inits connection
     */
    private function init()
    {
        $this->isConnected = false;
        $this->isResponse = false;
        $this->setStateId($this->connectId);
        $this->connection->setStateId($this->connectId);
        $this->responseData = null;
    }

    /**
     * Creates new cluster
     */
    private function newData()
    {
        $this->request = new SkynetRequest();
        $this->response = new SkynetResponse();
        $this->request->setStateId($this->connectId);
        $this->response->setStateId($this->connectId);
        $this->connection->setStateId($this->connectId);
    }

    /**
     * Logs connection data
     */
    private function saveConnectionData()
    {
        $this->connectionData = [
            'id' => $this->connectId,
            'CLUSTER URL' => $this->clusterUrl,
            'Ping' => $this->cluster->getHeader()->getPing() . 'ms',
            'FIELDS' => [
                'request_raw' => $this->request->getFields(),
                'response_decrypted' => $this->response->getFields(),
                'request_encypted' => $this->request->getEncryptedFields(),
                'response_raw' => $this->response->getRawFields()
            ],
            'SENDED RAW DATA' => $this->adapter['params'],
            'RECEIVED RAW DATA' => $this->responseData
        ];
    }

    /**
     * Returns cluster
     *
     * @return SkynetCluster Remote cluster
     */
    public function getCluster()
    {
        return $this->cluster;
    }

    /**
     * Returns monits
     *
     * @return string[] Monits
     */
    public function getMonits()
    {
        return $this->monits;
    }

    /**
     * Returns if added to DB
     *
     * @return string[] Monits
     */
    public function getAddition()
    {
        return $this->addition;
    }

    /**
     * Returns connection data
     *
     * @return string[] Connection debug data
     */
    public function getConnectionData()
    {
        return $this->connectionData;
    }

    /**
     * Returns signal to break broadcast
     *
     * @return bool True if stop broadcast
     */
    public function getBreakConnections()
    {
        return $this->breakConnections;
    }

    /**
     * Sets if broadcast mode
     *
     * @param bool $isBroadcast
     */
    public function setIsBroadcast($isBroadcast)
    {
        $this->isBroadcast = $isBroadcast;
    }

    /**
     * Sets if Client
     *
     * @param bool $isClient
     */
    public function setIsClient($isClientt)
    {
        $this->isClient = $isClient;
    }

    /**
     * Assigns Request
     *
     * @param SkynetRequest $request
     */
    public function assignRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Assigns Response
     *
     * @param SkynetResponse $response
     */
    public function assignResponse($response)
    {
        $this->response = $response;
    }

    /**
     * Assigns clusters list
     *
     * @param SkynetCluster[] $clusters
     */
    public function assignClusters($clusters)
    {
        $this->clusters = $clusters;
    }

    /**
     * Assigns connect ID
     *
     * @param int $connectId
     */
    public function assignConnectId($connectId)
    {
        $this->connectId = $connectId;
    }

    /**
     * Assigns cluster URL
     *
     * @param string $clusterUrl
     */
    public function assignClusterUrl($clusterUrl)
    {
        $this->clusterUrl = $clusterUrl;
    }

    /**
     * Assigns CLI
     *
     * @param SkynetCli $cli
     */
    public function assignCli($cil)
    {
        $this->cil = $cil;
    }

    /**
     * Assigns Console
     *
     * @param SkynetConsole $console
     */
    public function assignConsole($console)
    {
        $this->console = $console;
    }
}