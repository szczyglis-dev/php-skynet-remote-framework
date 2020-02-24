<?php

/**
 * Skynet/EventListener/SkynetEventListenerAbstract.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\EventListener;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\Secure\SkynetAuth;
use Skynet\Core\SkynetChain;
use Skynet\Data\SkynetRequest;
use Skynet\Data\SkynetResponse;
use Skynet\Database\SkynetDatabase;
use Skynet\Common\SkynetHelper;
use Skynet\Data\SkynetParams;
use Skynet\Secure\SkynetVerifier;
use Skynet\Database\SkynetOptions;
use Skynet\Database\SkynetRegistry;
use Skynet\Cluster\SkynetClustersRegistry;
use Skynet\Console\SkynetConsole;
use Skynet\Console\SkynetCli;
use Skynet\Debug\SkynetDebug;
use Skynet\EventLogger\SkynetEventListenerLoggerDatabase;
use Skynet\EventLogger\SkynetEventListenerLoggerFiles;

/**
 * Skynet Event Listener Abstract
 *
 * Base class for all Event Listeners
 * Every Event Listener must extends this class and implements [SkynetEventListenerInterface]
 *
 * @uses SkynetErrorsTrait
 * @uses SkynetStatesTrait
 */
abstract class SkynetEventListenerAbstract
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var SkynetResponse Assigned response */
    protected $response;

    /** @var SkynetRequest Assigned request */
    protected $request;

    /** @var string[] Array of indexed responses */
    protected $responseData;

    /** @var string[] Array of indexed requests */
    protected $requestsData;

    /** @var bool Status of database connection */
    protected $db_connected = false;

    /** @var bool Status of table schema in database, true if all tables exists */
    protected $db_created = false;

    /** @var string Url of receiver */
    protected $receiverClusterUrl;

    /** @var string Url of sender */
    protected $senderClusterUrl;

    /** @var SkynetDatabase */
    protected $database;

    /** @var PDO PDO connection instance */
    protected $db;

    /** @var bool Is sender or receiver */
    protected $sender = true;

    /** @var string Context of call - sender or cluster */
    protected $mode;

    /** @var string My cluster URL */
    protected $myAddress;

    /** @var string URL of sender */
    protected $senderAddress;

    /** @var integer Connection Number/ID */
    protected $connId;

    /** @var SkynetVerifier SkynetVerifier instance */
    protected $verifier;

    /** @var SkynetParams Params Operations */
    protected $paramsParser;

    /** @var SkynetAuth Authentication */
    protected $auth;

    /** @var SkynetOptions Options registry */
    protected $options;

    /** @var SkynetRegistry Registry */
    protected $registry;

    /** @var SkynetCli CLI Console */
    protected $cli;

    /** @var SkynetConsole HTML Console */
    protected $console;

    /** @var SkynetDebug Debugger */
    protected $debug;

    /** @var string[] Monits */
    protected $monits = [];

    /** @var string Event */
    protected $eventName;

    /** @var string Context */
    protected $context;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadErrorsRegistry();
        $this->loadStatesRegistry();
        $this->auth = new SkynetAuth();
        $this->database = SkynetDatabase::getInstance();
        $this->db_connected = $this->database->isDbConnected();
        $this->db_created = $this->database->isDbCreated();
        $this->db = $this->database->getDB();
        $this->myAddress = SkynetHelper::getMyUrl();
        $this->verifier = new SkynetVerifier();
        $this->paramsParser = new SkynetParams();
        $this->options = new SkynetOptions();
        $this->registry = new SkynetRegistry();
        $this->debug = new SkynetDebug();
    }

    /**
     * Adds monit
     *
     * @param string $mode
     */
    public function addMonit($monit)
    {
        $tmpMonit = $monit;
        if (is_array($monit)) {
            $tmpMonit = implode('<br>', $monit);
        }
        $this->monits[] = $tmpMonit;
    }

    /**
     * Sets event name
     *
     * @param string $event
     */
    public function setEventName($event)
    {
        $this->eventName = $event;
    }

    /**
     * Sets context
     *
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Sets context - sender or cluster
     *
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Sets actual connection number/id
     *
     * @param integer $id
     */
    public function setConnId($id)
    {
        $this->connId = $id;
        $this->stateId = $id;
    }

    /**
     * Assigns response data array
     *
     * @param string[] $data
     */
    public function setResponseData($data)
    {
        $this->responseData = $data;
    }

    /**
     * Assigns request data array
     *
     * @param string[] $data
     */
    public function setRequestData($data)
    {
        $this->requestsData = $data;
    }

    /**
     * Assigns $response object to Skynet
     *
     * @param SkynetResponse $response
     */
    public function assignResponse(SkynetResponse $response)
    {
        $this->response = $response;
        $this->responseData = $this->response->getResponseData();
    }

    /**
     * Assigns $request object to Skynet
     *
     * @param SkynetRequest $request
     */
    public function assignRequest(SkynetRequest $request)
    {
        $this->request = $request;
        $this->requestsData = $this->request->getRequestsData();
    }

    /**
     * Sets URL address of cluster witch Skynet sending to
     *
     * @param string $url
     */
    public function setReceiverClusterUrl($url)
    {
        $this->receiverClusterUrl = SkynetHelper::cleanUrl($url);
    }

    /**
     * Sets URL address of sender cluster
     *
     * @param string $url
     */
    public function setSenderClusterUrl($url)
    {
        $this->senderClusterUrl = SkynetHelper::cleanUrl($url);
    }

    /**
     * Sets if I'm sender
     *
     * @param bool $isSender
     */
    public function setSender($isSender)
    {
        $this->sender = $isSender;
    }

    /**
     * Returns packed params (alias)
     *
     * @param mixed[] $params Params array
     */
    protected function packParams($params)
    {
        return $this->paramsParser->packParams($params);
    }

    /**
     * Returns unpacked params (alias)
     *
     * @param mixed $params Packed params string
     */
    protected function unpackParams($params)
    {
        return $this->paramsParser->unpackParams($params);
    }

    /**
     * Checks for params is packed (alias)
     *
     * @param bool True if packed
     */
    protected function isPacked($params)
    {
        return $this->paramsParser->isPacked($params);
    }

    /**
     * Gets Registry value
     *
     * @param string $key Key
     *
     * @return mixed Value
     */
    protected function reg_get($key)
    {
        return $this->registry->getRegistryValue($key);
    }

    /**
     * Updates Registry value
     *
     * @param string $key Key
     * @param string $value value
     *
     * @return bool
     */
    protected function reg_set($key, $value)
    {
        return $this->registry->setRegistryValue($key, $value);
    }

    /**
     * Gets Option value
     *
     * @param string $key Key
     *
     * @return mixed Value
     */
    protected function opt_get($key)
    {
        return $this->options->getOptionsValue($key);
    }

    /**
     * Updates Option  value
     *
     * @param string $key Key
     * @param string $value value
     *
     * @return bool
     */
    protected function opt_set($key, $value)
    {
        return $this->options->setOptionsValue($key, $value);
    }

    /**
     * Assigns CLI
     *
     * @param SkynetCli $cli
     */
    public function assignCli(SkynetCli $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Assigns Console
     *
     * @param SkynetConsole $console
     */
    public function assignConsole(SkynetConsole $console)
    {
        $this->console = $console;
    }

    /**
     * Resets monits
     */
    public function resetMonits()
    {
        $this->monits = [];
    }

    /**
     * Adds log
     *
     * @param string $content Log message
     *
     * @return bool True if success
     */
    public function addLog($content = '')
    {
        $loggerDb = new SkynetEventListenerLoggerDatabase();
        $loggerDb->setSenderClusterUrl($this->senderClusterUrl);
        $loggerDb->setReceiverClusterUrl($this->receiverClusterUrl);

        $loggerTxt = new SkynetEventListenerLoggerFiles();
        $loggerTxt->setSenderClusterUrl($this->senderClusterUrl);
        $loggerTxt->setReceiverClusterUrl($this->receiverClusterUrl);

        $argsStr = '';
        $file = '';
        $line = '';
        $method = '';
        $args = [];

        $success = false;

        $listener = pathinfo(@debug_backtrace()[0]['file'], PATHINFO_FILENAME);
        $line = @debug_backtrace()[0]['line'];
        $method = @debug_backtrace()[1]['function'];
        $args = @debug_backtrace()[1]['args'];

        $argsAry = [];
        if (is_array($args) && count($args) > 0) {
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    $argsAry[] = implode(':', $arg);
                } elseif (is_object($arg)) {
                    $argsAry[] = get_class($arg);
                } else {
                    $argsAry[] = $arg;
                }
            }
            $argsStr = implode(',', $argsAry);
        }

        $eventStr = $this->eventName . '(' . $this->context . ')';
        $methodStr = $method . '(' . $argsStr . ')';

        if ($loggerDb->saveUserLogToDb($content, $listener, $line, $eventStr, $methodStr)) {
            $success = true;
        }

        if ($loggerTxt->saveUserLogToFile($content, $listener, $line, $eventStr, $methodStr)) {
            $success = true;
        }

        return $success;
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