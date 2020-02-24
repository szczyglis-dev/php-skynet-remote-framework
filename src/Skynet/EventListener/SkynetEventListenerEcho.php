<?php

/**
 * Skynet/EventListener/SkynetEventListenerEcho.php
 *
 * @package Skynet
 * @version 1.1.3
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\EventListener;

use Skynet\Common\SkynetTypes;
use Skynet\Common\SkynetHelper;
use Skynet\Core\SkynetPeer;
use Skynet\Cluster\SkynetClustersUrlsChain;
use Skynet\Cluster\SkynetClustersRegistry;
use Skynet\EventLogger\SkynetEventListenerLoggerFiles;
use Skynet\EventLogger\SkynetEventListenerLoggerDatabase;
use SkynetUser\SkynetConfig;

/**
 * Skynet Event Listener - Echo
 *
 * Creates and operates on Echo and Broadcast Requests
 */
class SkynetEventListenerEcho extends SkynetEventListenerAbstract implements SkynetEventListenerInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * onConnect Event
     *
     * Actions executes when onConnect event is fired
     *
     * @param SkynetConnectionInterface $conn Connection adapter instance
     */
    public function onConnect($conn = null)
    {
    }


    /**
     * onRequest Event
     *
     * Actions executes when onRequest event is fired
     * Context: beforeSend - executes in sender when creating request.
     * Context: afterReceive - executes in responder when request received from sender.
     *
     * @param string $context Context - beforeSend | afterReceive
     */
    public function onRequest($context = null)
    {
        if ($context == 'beforeSend') {
        }
    }

    /**
     * onResponse Event
     *
     * Actions executes when onResponse event is fired.
     * Context: beforeSend - executes in responder when creating response for request.
     * Context: afterReceive - executes in sender when response for request is received from responder.
     *
     * @param string $context Context - beforeSend | afterReceive
     */
    public function onResponse($context = null)
    {
        if ($context == 'afterReceive') {
        }

        if ($context == 'beforeSend') {
        }
    }

    /**
     * onBroadcast Event
     *
     * Actions executes when onBroadcast event is fired.
     * Context: beforeSend - executes in responder when @broadcast command received from request.
     * Context: afterReceive - executes in sender when response for @broadcast received.
     *
     * @param string $context Context - beforeSend | afterReceive
     */
    public function onBroadcast($context = null)
    {
        if ($context == 'beforeSend') {
            /* Ping to all my clusters */
            $this->pingAll($context);
            foreach ($this->requestsData as $k => $v) {
                /* If not internal skynet param */
                if (strpos($k, '_skynet') !== 0 && strpos($k, '_skynet') !== 1 && strpos($k, '<<') !== 0 && strpos($k, '<<') !== 1) {
                    /* Then resend Request */
                    $this->response->set($k, $v);
                }
            }
        }
    }

    /**
     * onEcho Event
     *
     * Actions executes when onEcho event is fired.
     * Context: beforeSend - executes in responder when @echo command received from request.
     * Context: afterReceive - executes in sender when response for @echo received.
     *
     * @param string $context Context - beforeSend | afterReceive
     */
    public function onEcho($context = null)
    {
        if ($context == 'beforeSend') {
            if ($this->request->get('@broadcast') == 1) {
                return false;
            }
            $this->pingAll($context);
        }
    }

    /**
     * onCli Event
     *
     * Actions executes when CLI command in input
     * Access to CLI: $this->cli
     */
    public function onCli()
    {

    }

    /**
     * onConsole Event
     *
     * Actions executes when HTML Console command in input
     * Access to Console: $this->console
     */
    public function onConsole()
    {

    }

    /**
     * Registers commands
     *
     * Must returns:
     * ['cli'] - array with cli commands [command, description]
     * ['console'] - array with console commands [command, description]
     *
     * @return array[] commands
     */
    public function registerCommands()
    {
        $cli = [];
        $console = [];

        $console[] = ['@echo', ['cluster address', 'cluster address1, address2 ...'], 'TO ALL'];
        $console[] = ['@broadcast', ['cluster address', 'cluster address1, address2 ...'], 'TO ALL'];

        return array('cli' => $cli, 'console' => $console);
    }

    /**
     * Registers database tables
     *
     * Must returns:
     * ['queries'] - array with create/insert queries
     * ['tables'] - array with tables names
     * ['fields'] - array with tables fields definitions
     *
     * @return array[] tables data
     */
    public function registerDatabase()
    {
        $queries = [];
        $tables = [];
        $fields = [];
        return array('queries' => $queries, 'tables' => $tables, 'fields' => $fields);
    }

    /**
     * Sends echo and broadcast messages
     *
     * @param string $context Context - beforeSend | afterReceive
     */
    private function pingAll($context)
    {

        /* Must been launch "beforeSend" IN RESPONDER (before sending Response).
        Echo is sending from Peer to next clusters with request to another echo - clusters sends next echo via own Peers when responding on request from here.
        No response is generated here, cluster don't show response data on echo.  */
        if ($context != 'beforeSend' || $this->opt_get('sleep') == 1) {
            return false;
        }

        /* prepare/check already visited clusters chain */
        $urlsChain = new SkynetClustersUrlsChain();
        $urlsChain->assignRequest($this->request);

        $urlsChain->loadFromRequest();
        if ($urlsChain->isMyClusterInChain()) {
            return false;
        }

        $urlsChain->addMyClusterToChain();
        if (!$urlsChain->isSenderClusterInChain()) {
            $urlsChain->addSenderClusterToChain();
        }
        $newPingedChainRaw = $urlsChain->getClustersUrlsChain();


        /* get clusters */
        $connectAddresses = [];
        $rawAddresses = [];
        $clustersRegistry = new SkynetClustersRegistry();
        $clusters = $clustersRegistry->getAll();
        if (count($clusters) > 0) {
            foreach ($clusters as $cluster) {
                if ($cluster->getUrl() != $this->request->getSenderClusterUrl() && !$urlsChain->isClusterInChain($cluster->getUrl())) {
                    $rawAddresses[] = $cluster->getUrl();
                    $connectAddresses[] = SkynetConfig::get('core_connection_protocol') . $cluster->getUrl();
                }
            }

            if (is_array($connectAddresses) && count($connectAddresses) > 0) {
                $skynetPeer = new SkynetPeer();
                $skynetPeer->getRequest()->set('_skynet_clusters_chain', $newPingedChainRaw);

                /* If in echo mode */
                if ($this->request->get('@echo') == 1) {
                    $skynetPeer->getRequest()->set('@echo', 1);
                }

                /* If in broadcast mode */
                if ($this->request->get('@broadcast') == 1) {
                    $broadcastedRequests = [];
                    foreach ($this->requestsData as $k => $v) {
                        /* If not internal skynet param */
                        if (!$this->verifier->isInternalParameter($k)) {
                            $skynetPeer->getRequest()->set($k, $v);
                            $this->response->set($k, $v);
                            $broadcastedRequests[$k] = $v;
                        }
                    }
                    $skynetPeer->getRequest()->set('@broadcast', 1);
                }

                /* Connect to clusters */
                $data = '';
                foreach ($connectAddresses as $address) {
                    $data .= $skynetPeer->connect($address);
                }

                /* No reponse is send */
                $this->response->set('FromEcho', $data);


                /* Save logs */
                if ($this->request->get('@echo') == 1) {
                    if (SkynetConfig::get('logs_db_echo')) {
                        $logger = new SkynetEventListenerLoggerDatabase();
                        $logger->assignRequest($this->request);
                        $logger->setRequestData($this->requestsData);
                        if ($logger->saveEchoToDb($rawAddresses, $urlsChain)) {
                            $this->addState(SkynetTypes::STATUS_OK, 'ECHO SAVED TO DB');
                        }
                    }

                    if (SkynetConfig::get('logs_txt_echo')) {
                        $logger = new SkynetEventListenerLoggerFiles();
                        $logger->assignRequest($this->request);
                        $logger->setRequestData($this->requestsData);
                        if ($logger->saveEchoToFile($rawAddresses, $urlsChain)) {
                            $this->addState(SkynetTypes::STATUS_OK, 'ECHO SAVED TO TXT');
                        }
                    }
                }

                if ($this->request->get('@broadcast') == 1) {
                    if (SkynetConfig::get('logs_db_broadcast')) {
                        $logger = new SkynetEventListenerLoggerDatabase();
                        $logger->assignRequest($this->request);
                        $logger->setRequestData($this->requestsData);
                        if ($logger->saveBroadcastToDb($rawAddresses, $urlsChain)) {
                            $this->addState(SkynetTypes::STATUS_OK, 'BROADCAST SAVED TO DB');
                        }
                    }

                    if (SkynetConfig::get('logs_txt_broadcast')) {
                        $logger = new SkynetEventListenerLoggerFiles();
                        $logger->assignRequest($this->request);
                        $logger->setRequestData($this->requestsData);
                        if ($logger->saveBroadcastToFile($rawAddresses, $urlsChain, $broadcastedRequests)) {
                            $this->addState(SkynetTypes::STATUS_OK, 'BROADCAST SAVED TO TXT');
                        }
                    }
                }
            }
        }

        if ($this->request->get('@echo') == 1) {
            $this->response->set('@echo', '1');
        }
        if ($this->request->get('@broadcast') == 1) {
            $this->response->set('@broadcast', '1');
        }
        $this->response->set('_skynet_clusters_chain', $newPingedChainRaw);
    }
}