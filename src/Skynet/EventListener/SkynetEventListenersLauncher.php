<?php

/**
 * Skynet/EventListener/SkynetEventListenersLauncher.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.1.1
 */

namespace Skynet\EventListener;

use Skynet\EventListener\SkynetEventListenersFactory;
use Skynet\EventLogger\SkynetEventLoggersFactory;
use Skynet\Common\SkynetHelper;

/**
 * Skynet Event Listeners Launcher
 *
 */
class SkynetEventListenersLauncher
{
    /** @var SkynetRequest Request object */
    private $request;

    /** @var SkynetResponse Response object */
    private $response;

    /** @var int Connection Number */
    private $connectId = 1;

    /** @var string Cluster UR */
    private $clusterUrl;

    /** @var string Sender cluster UR */
    private $senderClusterUrl;

    /** @var string Receiver cluster UR */
    private $receiverClusterUrl;

    /** @var SkynetCli CLI object */
    private $cli;

    /** @var SkynetConsole Console object */
    private $console;

    /** @var SkynetEventListenerInterface[] Lisetners */
    private $eventListeners;

    /** @var SkynetEventListenerInterface[] Loggers */
    private $eventLoggers;

    /** @var string[] Output from CLI */
    private $cliOutput = [];

    /** @var string[] Output from Console */
    private $consoleOutput = [];

    /** @var bool If sender */
    private $sender = true;

    /** @var string[] Tables data */
    private $dbTables = [];

    /** @var string[] Monits */
    private $monits = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->eventListeners = SkynetEventListenersFactory::getInstance()->getEventListeners();
        $this->eventLoggers = SkynetEventLoggersFactory::getInstance()->getEventListeners();
    }

    /**
     * Assigns request
     *
     * @param SkynetRequest $request
     */
    public function assignRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Assigns response
     *
     * @param SkynetResponse $response
     */
    public function assignResponse($response)
    {
        $this->response = $response;
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
     * Assigns clusterURL
     *
     * @param string $clusterUrl
     */
    public function assignClusterUrl($clusterUrl)
    {
        $this->clusterUrl = $clusterUrl;
    }

    /**
     * Assigns sender clusterURL
     *
     * @param string $clusterUrl
     */
    public function assignSenderClusterUrl($clusterUrl)
    {
        $this->senderClusterUrl = $clusterUrl;
    }

    /**
     * Assigns receiver clusterURL
     *
     * @param string $clusterUrl
     */
    public function assignReceiverClusterUrl($clusterUrl)
    {
        $this->receiverClusterUrl = $clusterUrl;
    }

    /**
     * Assigns CLI
     *
     * @param SkynetCli CLI
     */
    public function assignCli($cli)
    {
        $this->cli = $cli;
    }

    public function assignConsole($console)
    {
        $this->console = $console;
    }

    /**
     * Assigns Cli output data
     *
     * @param string[] Output from CLI
     */
    public function getCliOutput()
    {
        return $this->cliOutput;
    }

    /**
     * Assigns Console output data
     *
     * @param string[] Output from Console
     */
    public function getConsoleOutput()
    {
        return $this->consoleOutput;
    }

    /**
     * Sets if sender
     *
     * @param bool True if sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * Launch Event Listeners
     *
     * Method execute all registered in Factory event listeners. Every listener have access to request and response and can manipulate them.
     * You can create and register your own listeners by added them to registry in {SkynetEventListenersFactory}.
     * Every event listener must implements {SkynetEventListenerInterface} interface and extends {SkynetEventListenerAbstract} class.
     * OnEventName() method gets context param {beforeSend|afterReceive} (you can depends actions from that).
     * Inside event listener you have access to $request and $response objects. See API documentation for more info.
     *
     * @param string $event Event name
     */
    public function launch($event)
    {
        switch ($this->sender) {
            case true:
                $this->launchSenderListeners($event);
                break;

            case false:
                $this->launchResponderListeners($event);
                break;
        }
    }


    private function get_class_name($classname)
    {
        if ($pos = strrpos($classname, '\\')) {
            return substr($classname, $pos + 1);
        }
        return $pos;
    }

    /**
     * Assigns monits from listeners
     *
     * @param string[] Monits from listener
     */
    private function assignMonits($listener, $eventName, $context = null)
    {
        $monits = $listener->getMonits();
        if (is_array($monits) && count($monits) > 0) {
            $this->monits[] = '[' . $this->get_class_name(get_class($listener)) . '] : ' . $eventName . '(' . $context . ')<br>' . implode('<br>', $monits) . '<br>';
        }
    }

    /**
     * Launch Event Listeners
     *
     * Method execute all registered in Factory event listeners. Every listener have access to request and response and can manipulate them.
     * You can create and register your own listeners by added them to registry in {SkynetEventListenersFactory}.
     * Every event listener must implements {SkynetEventListenerInterface} interface and extends {SkynetEventListenerAbstract} class.
     * OnEventName() method gets context param {beforeSend|afterReceive} (you can depends actions from that).
     * Inside event listener you have access to $request and $response objects. See API documentation for more info.
     *
     * @param string $event Event name
     */
    private function launchSenderListeners($event)
    {
        switch ($event) {
            /* Launch when response received */
            case 'onResponse':
                foreach ($this->eventListeners as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onResponse');
                    $listener->setContext('afterReceive');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $listener->assignRequest($this->request);
                    $listener->assignResponse($this->response);
                    if (method_exists($listener, 'onResponse')) {
                        $listener->onResponse('afterReceive');
                    }
                    $requests = $this->request->getRequestsData();
                    if (isset($requests['@echo'])) {
                        if (method_exists($listener, 'onEcho')) {
                            $listener->onEcho('afterReceive');
                        }
                    }
                    if (isset($requests['@broadcast'])) {
                        if (method_exists($listener, 'onBroadcast')) {
                            $listener->onBroadcast('afterReceive');
                        }
                    }
                    $this->assignMonits($listener, $event, 'afterReceive');
                }
                break;

            /* Launch before sending request */
            case 'onRequest':
                foreach ($this->eventListeners as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onRequest');
                    $listener->setContext('beforeSend');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $listener->assignRequest($this->request);
                    $listener->assignResponse($this->response);
                    $listener->setReceiverClusterUrl($this->clusterUrl);
                    if (method_exists($listener, 'onRequest')) {
                        $listener->onRequest('beforeSend');
                    }
                    $requests = $this->request->getRequestsData();
                    $this->assignMonits($listener, $event, 'beforeSend');
                }

                if ($this->request->isField('@broadcast')
                    && !$this->request->isField('@broadcaster')) {
                    $this->request->set('@broadcaster', SkynetHelper::getMyUrl());
                }

                break;

            /* Launch after response listeners */
            case 'onResponseLoggers':
                foreach ($this->eventLoggers as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onResponseLoggers');
                    $listener->setContext('afterReceive');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $listener->assignRequest($this->request);
                    $listener->assignResponse($this->response);
                    if (method_exists($listener, 'onResponse')) {
                        $listener->onResponse('afterReceive');
                    }
                    $requests = $this->request->getRequestsData();
                    if (isset($requests['@echo'])) {
                        if (method_exists($listener, 'onEcho')) {
                            $listener->onEcho('afterReceive');
                        }
                    }
                    if (isset($requests['@broadcast'])) {
                        if (method_exists($listener, 'onBroadcast')) {
                            $listener->onBroadcast('afterReceive');
                        }
                    }
                    $this->assignMonits($listener, $event, 'afterReceive');
                }
                break;

            /* Launch after request listeners */
            case 'onRequestLoggers':
                foreach ($this->eventLoggers as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onRequestLoggers');
                    $listener->setContext('beforeSend');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $listener->assignRequest($this->request);
                    $listener->assignResponse($this->response);
                    $listener->setReceiverClusterUrl($this->clusterUrl);
                    if (method_exists($listener, 'onRequest')) {
                        $listener->onRequest('beforeSend');
                    }
                    $requests = $this->request->getRequestsData();
                    $this->assignMonits($listener, $event, 'beforeSend');
                }
                break;

            /* Launch when CLI */
            case 'onCli':
                foreach ($this->eventListeners as $listener) {
                    $listener->resetMonits();
                    $listener->setEventName('onCli');
                    $listener->setContext('onCli');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->assignCli($this->cli);
                    if (method_exists($listener, 'onCli')) {
                        $output = $listener->onCli();
                    }
                    if ($output !== null) {
                        $this->cliOutput[] = $output;
                    }
                    $this->assignMonits($listener, $event, 'onCli');
                }
                break;

            /* Launch when Console */
            case 'onConsole':
                foreach ($this->eventListeners as $listener) {
                    $listener->resetMonits();
                    $listener->setEventName('onConsole');
                    $listener->setContext('onConsole');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->assignConsole($this->console);
                    if (method_exists($listener, 'onConsole')) {
                        $output = $listener->onConsole();
                    }
                    if ($output !== null) {
                        $this->consoleOutput[] = $output;
                    }
                    $this->assignMonits($listener, $event, 'onConsole');
                }
                break;
        }
    }

    /**
     * Launch Event Listeners
     *
     * Method execute all registered in Factory event listeners. Every listener have access to request and response and can manipulate them.
     * You can create and register your own listeners by added them to registry in {SkynetEventListenersFactory}.
     * Every event listener must implements {SkynetEventListenerInterface} interface and extends {SkynetEventListenerAbstract} class.
     * OnEventName() method gets context param {beforeSend|afterReceive} (you can depends actions from that).
     * Inside event listener you have access to $request and $response objects. See API documentation for more info.
     *
     * @param string $event Event name
     */
    private function launchResponderListeners($event)
    {
        switch ($event) {
            /* Launch before sending response */
            case 'onResponse':
                foreach ($this->eventListeners as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onResponse');
                    $listener->setContext('beforeSend');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $this->request->loadRequest();
                    $listener->assignRequest($this->request);
                    $this->response->parseResponse();
                    $requests = $this->request->getRequestsData();
                    $listener->setRequestData($requests);
                    $listener->assignResponse($this->response);
                    if (isset($requests['_skynet']) && isset($requests['_skynet_sender_url']) && $requests['_skynet_sender_url'] != SkynetHelper::getMyUrl()) {
                        if (method_exists($listener, 'onResponse')) {
                            $listener->onResponse('beforeSend');
                        }
                        if (isset($requests['@echo'])) {
                            if (method_exists($listener, 'onEcho')) {
                                $listener->onEcho('beforeSend');
                            }
                        }
                        if (isset($requests['@broadcast'])) {
                            if (method_exists($listener, 'onBroadcast')) {
                                $listener->onBroadcast('beforeSend');
                            }
                        }
                    }
                    $this->assignMonits($listener, $event, 'beforeSend');
                }
                break;

            /* Launch after receives request */
            case 'onRequest':
                foreach ($this->eventListeners as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onRequest');
                    $listener->setContext('afterReceive');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $this->request->loadRequest();
                    $listener->assignRequest($this->request);
                    $this->response->parseResponse();
                    $requests = $this->request->getRequestsData();
                    $listener->setRequestData($requests);
                    $listener->assignResponse($this->response);
                    if (isset($requests['_skynet']) && isset($requests['_skynet_sender_url']) && $requests['_skynet_sender_url'] != SkynetHelper::getMyUrl()) {
                        if (method_exists($listener, 'onRequest')) {
                            $listener->onRequest('afterReceive');
                        }
                    }
                    $this->assignMonits($listener, $event, 'afterReceive');
                }
                break;

            /* Launch after response listeners */
            case 'onResponseLoggers':
                foreach ($this->eventLoggers as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onResponseLoggers');
                    $listener->setContext('beforeSend');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $this->request->loadRequest();
                    $listener->assignRequest($this->request);
                    $this->response->parseResponse();
                    $requests = $this->request->getRequestsData();
                    $listener->setRequestData($requests);
                    $listener->assignResponse($this->response);
                    if (isset($requests['_skynet']) && isset($requests['_skynet_sender_url']) && $requests['_skynet_sender_url'] != SkynetHelper::getMyUrl()) {
                        if (method_exists($listener, 'onResponse')) {
                            $listener->onResponse('beforeSend');
                        }
                        if (isset($requests['@echo'])) {
                            if (method_exists($listener, 'onEcho')) {
                                $listener->onEcho('beforeSend');
                            }
                        }
                        if (isset($requests['@broadcast'])) {
                            if (method_exists($listener, 'onBroadcast')) {
                                $listener->onBroadcast('beforeSend');
                            }
                        }
                    }
                    $this->assignMonits($listener, $event, 'beforeSend');
                }
                break;

            /* Launch after request listeners */
            case 'onRequestLoggers':
                foreach ($this->eventLoggers as $listener) {
                    $listener->resetMonits();
                    $listener->setConnId($this->connectId);
                    $listener->setEventName('onRequestLoggers');
                    $listener->setContext('afterReceive');

                    $listener->setReceiverClusterUrl($this->receiverClusterUrl);
                    $listener->setSenderClusterUrl($this->senderClusterUrl);

                    $listener->setSender($this->sender);
                    $this->request->loadRequest();
                    $listener->assignRequest($this->request);
                    $this->response->parseResponse();
                    $requests = $this->request->getRequestsData();
                    $listener->setRequestData($requests);
                    $listener->assignResponse($this->response);
                    if (isset($requests['_skynet']) && isset($requests['_skynet_sender_url']) && $requests['_skynet_sender_url'] != SkynetHelper::getMyUrl()) {
                        if (method_exists($listener, 'onRequest')) {
                            $listener->onRequest('afterReceive');
                        }
                    }
                    $this->assignMonits($listener, $event, 'afterReceive');
                }
                break;
        }
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
}