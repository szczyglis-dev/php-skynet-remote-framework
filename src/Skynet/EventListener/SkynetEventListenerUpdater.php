<?php

/**
 * Skynet/EventListener/SkynetEventListenerEcho.php
 *
 * @package Skynet
 * @version 1.1.6
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\EventListener;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\Common\SkynetTypes;
use Skynet\Common\SkynetHelper;
use Skynet\Connection\SkynetConnectionsFactory;
use Skynet\Database\SkynetDatabase;
use Skynet\Secure\SkynetVerifier;
use Skynet\Encryptor\SkynetEncryptorsFactory;
use Skynet\EventLogger\SkynetEventListenerLoggerFiles;
use Skynet\EventLogger\SkynetEventListenerLoggerDatabase;
use Skynet\SkynetVersion;
use Skynet\Error\SkynetException;
use SkynetUser\SkynetConfig;

/**
 * Skynet Event Listener - Echo
 *
 * Creates and operates on Echo and Broadcast Requests
 */
class SkynetEventListenerUpdater extends SkynetEventListenerAbstract implements SkynetEventListenerInterface
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
            if ($this->response->get('@<<self_updateSuccess') !== null) {
                $this->addMonit('[SUCCESS] Update succesfull: ' . $this->response->get('@<<self_updateSuccess'));
            }
            if ($this->response->get('@<<self_updateError') !== null) {
                $this->addMonit('[ERROR] Update error: ' . $this->response->get('@<<self_updateError'));
            }
        }

        if ($context == 'beforeSend') {
            if ($this->request->get('@self_update') !== null) {
                if (isset($this->request->get('@self_update')['source'])) {
                    $address = $this->request->get('@self_update')['source'];
                } else {
                    $this->response->set('@<<self_updateError', 'NO SOURCE: ' . SkynetHelper::getMyUrl());
                    return false;
                }

                if ($address == SkynetHelper::getMyUrl()) {
                    return false;
                }

                try {
                    $success = false;
                    $logs = [];
                    $data = null;
                    $logs[] = 'SELF-UPDATE: REQUEST RECEIVED';
                    $remote = $this->getRemoteCode($address);
                    $logs[] = 'SELF-UPDATE: REMOTE SOURCE: ' . $address;

                    if (isset($remote['data'])) {
                        $data = $remote['data'];
                    } else {
                        throw new SkynetException('SELF-UPDATE ERROR: NULL');
                    }

                    /* If is data received */
                    if ($data !== null && !empty($data)) {
                        $logs[] = 'SELF-UPDATE: REMOTE DATA RECEIVED';
                        $encodedData = json_decode($data);
                    } else {
                        $logs[] = 'SELF-UPDATE ERROR: NO DATA RECEIVED';
                        throw new SkynetException('SELF-UPDATE ERROR: NO DATA RECEIVED');
                    }

                    /* If is version info */
                    if (isset($encodedData->version)) {
                        $logs[] = 'SELF-UPDATE: REMOTE VERSION IS: ' . $encodedData->version;
                        $logs[] = 'SELF-UPDATE: MY VERSION IS: ' . SkynetVersion::VERSION;
                        $new_version = $encodedData->version;
                        $new_code = $encodedData->code;
                    } else {
                        $logs[] = 'SELF-UPDATE ERROR: NO VERSION DATA';
                        throw new SkynetException('SELF-UPDATE ERROR: NO VERSION DATA');
                    }

                    /* If is new source code */
                    if (!empty($new_code)) {
                        $sended_checksum = $encodedData->checksum;
                        $gen_checksum = md5($new_code);
                        $logs[] = 'SELF-UPDATE: RECEIVED CODE CHECKSUM [sended]: ' . $sended_checksum;
                        $logs[] = 'SELF-UPDATE: RECEIVED CODE CHECKSUM [checked]: ' . $gen_checksum;
                    } else {
                        $logs[] = 'SELF-UPDATE ERROR: RECEIVED CODE EMPTY';
                        throw new SkynetException('SELF-UPDATE ERROR: RECEIVED CODE EMPTY');
                    }

                    /* Try to update */
                    if ($this->updateSourceCode($new_code)) {
                        $success = true;
                        $logs[] = 'SELF-UPDATE: UPDATED TO VERSION: ' . $encodedData->version;
                    } else {
                        $logs[] = 'SELF-UPDATE ERROR: UPDATE FAILED';
                        $success = false;
                        throw new SkynetException('SELF-UPDATE ERROR: UPDATE FAILED');
                    }

                } catch (SkynetException $e) {
                    $success = false;
                    $this->addError(SkynetTypes::UPDATER, 'SELF UPDATE FAILED: ' . $e->getMessage(), $e);
                }

                /* Save log to file */
                if ($this->areErrors() && is_array($this->getErrors())) {
                    $logs = array_merge($logs, $this->getErrors());
                }

                if (SkynetConfig::get('logs_txt_selfupdate')) {
                    $logger = new SkynetEventListenerLoggerFiles();
                    $logger->assignRequest($this->request);
                    $logger->setRequestData($this->requestsData);
                    if ($logger->saveSelfUpdateToFile($logs)) {
                        $this->addState(SkynetTypes::STATUS_OK, 'SELF-UPDATE STATUS SAVED TO TXT');
                    }
                }

                if (SkynetConfig::get('logs_db_selfupdate')) {
                    $data = [];
                    $data['source'] = $address;
                    $data['version'] = '';
                    if (isset($encodedData->version)) {
                        $data['version'] = $encodedData->version;
                    }

                    $logger = new SkynetEventListenerLoggerDatabase();
                    $logger->assignRequest($this->request);
                    $logger->setRequestData($this->requestsData);
                    if ($logger->saveSelfUpdateToDb($data, $logs)) {
                        $this->addState(SkynetTypes::STATUS_OK, 'SELFUPDATE SAVED TO DB');
                    }
                }

                if (!$success) {
                    $this->response->set('@<<self_updateError', SkynetHelper::getMyUrl());
                } else {
                    $this->response->set('@<<self_updateSuccess', SkynetHelper::getMyUrl());
                }
            }
        }
    }

    /**
     * Update Skynet with new source code
     *
     * @param string $code New PHP code
     *
     * @return bool
     */
    private function updateSourceCode($code)
    {
        if (empty($code)) {
            return false;
        }
        $myFile = SkynetHelper::getMyBasename();
        $tmpFile = '__' . $myFile;
        $bckMyFile = '___' . $myFile;

        try {
            if (!file_put_contents($tmpFile, $code)) {
                throw new SkynetException('File put error');
            }

            if (!rename($myFile, $bckMyFile)) {
                throw new SkynetException('Rename my file to backup failed');
            }

            if (!rename($tmpFile, $myFile)) {
                @rename($bckMyFile, $myFile);
                throw new SkynetException('Rename new file to my file failed');
            }
            @unlink($bckMyFile);
            return true;

        } catch (SkynetException $e) {
            $this->addError(SkynetTypes::UPDATER, 'Skynet update error: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Gets remote source code
     *
     * @param string $address Adress to cluster with code
     *
     * @return string Source code
     */
    private function getRemoteCode($address)
    {
        $connection = SkynetConnectionsFactory::getInstance()->getConnector(SkynetConfig::get('core_connection_type'));
        $encryptor = SkynetEncryptorsFactory::getInstance()->getEncryptor();
        $verifier = new SkynetVerifier();

        $ary = [];
        $ary['@code'] = 1;
        $ary['_skynet_hash'] = $verifier->generateHash();
        $ary['_skynet_id'] = $verifier->getKeyHashed();
        $ary['_skynet_sender_url'] = SkynetHelper::getMyUrl();
        $ary['_skynet_cluster_url'] = SkynetHelper::getMyUrl();

        if (!SkynetConfig::get('core_raw')) {
            $ary['@code'] = $encryptor->encrypt($ary['@code']);
            $ary['_skynet_hash'] = $encryptor->encrypt($ary['_skynet_hash']);
            $ary['_skynet_id'] = $encryptor->encrypt($ary['_skynet_id']);
            $ary['_skynet_sender_url'] = $encryptor->encrypt(SkynetHelper::getMyUrl());
            $ary['_skynet_cluster_url'] = $encryptor->encrypt(SkynetHelper::getMyUrl());
        }
        // $ary['_skynet_cluster_url']
        $connection->setRequests($ary);
        return $connection->connect($address);
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
        $console[] = ['@self_update', 'source:"source_cluster_address"', 'TO ALL'];
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
}