<?php

/**
 * Skynet/Connection/SkynetConnectionCurl.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Connection;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\SkynetVersion;
use Skynet\Common\SkynetTypes;
use Skynet\Error\SkynetException;
use SkynetUser\SkynetConfig;

/**
 * Skynet Connection [cURL]
 *
 * Adapter for cURL connections
 *
 * @uses SkynetErrorsTrait
 * @uses SkynetStatesTrait
 */
class SkynetConnectionCurl extends SkynetConnectionAbstract implements SkynetConnectionInterface
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var string[] Parameters array to send as POST via cURL */
    private $postParams = [];


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Connects to remote address and gets response data
     *
     * @param string|null $remote_address URL to connect
     *
     * @return string Raw received data
     */
    public function connect($remote_address = null)
    {
        $this->data = null;

        $address = '';

        if ($this->cluster !== null) {
            $address = $this->cluster->getUrl();
        }

        if ($remote_address !== null) {
            $address = $remote_address;
        }

        if (empty($address)) {
            $this->addState(SkynetTypes::CURL_ERR, '[[[[ERROR]]]: ADDRESS IS NULL');
            return false;
        }

        $this->url = $address;

        $this->prepareParams();
        $data = $this->init($this->url);
        $this->data = $data['data'];
        $result = $data['result'];

        try {
            if ($this->data === null) {
                throw new SkynetException('Connection error: [CURL] RESPONSE DATA IS NULL');
            }
            if (empty($this->data)) {
                $this->addState(SkynetTypes::CURL_ERR, '[[[[ERROR]]]: NO RESPONSE: ' . $this->url);
            } else {
                $this->launchConnectListeners($this);
            }

            $adapter = [];
            $adapter['result'] = $result;
            $adapter['data'] = $this->data;
            $adapter['params'] = $this->requests;
            return $adapter;

        } catch (SkynetException $e) {
            $this->addError(SkynetTypes::CURL, $e->getMessage(), $e);
        }
    }

    /**
     * Opens cURL connection and gets response data
     *
     * @param string $address URL to connect
     *
     * @return string Received raw data
     */
    private function init($address)
    {
        $result = null;
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $address);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Skynet ' . SkynetVersion::VERSION);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_VERBOSE, SkynetConfig::get('core_connection_curl_output'));
            if (!SkynetConfig::get('core_connection_ssl_verify')) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            $headers = array(
                "Cache-Control: no-cache",
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if (count($this->postParams) > 0) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postParams);
            }

            $responseData = curl_exec($ch);
            $charset = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            /* If errors */
            if (curl_errno($ch)) {
                $errno = curl_errno($ch);
                $msg = curl_error($ch);
                $this->addError(SkynetTypes::CURL, 'Connection error: [CURL] Code: ' . $msg . ' | Error: ' . $msg);
                $this->addState(SkynetTypes::CURL, 'CONNECTION ERROR: ' . $address);
                $result = false;
            }
            if ($responseData !== null && !empty($responseData)) {
                $this->addState(SkynetTypes::CURL, '[OK] RESPONSE DATA RECEIVED: ' . $this->url . ' (CHARSET: ' . $charset . ')');
                $preResponse = json_decode($responseData);
                if ($preResponse !== null && isset($preResponse->_skynet)) {
                    $result = true;
                }
            }

            curl_close($ch);

            $data = [];
            $data['result'] = $result;
            $data['data'] = $responseData;

            return $data;

        } catch (SkynetException $e) {
            $this->addError(SkynetTypes::CURL, 'CURL: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Create params for send via cURL
     * @param string[] $ary Array of params
     */
    private function createPostParams($ary)
    {
        $this->postParams = [];
        $this->postParams = $ary;
    }

    /**
     * Parse params into string (for debug)
     */
    public function prepareParams()
    {
        $fields = [];
        $this->postParams = [];

        if (is_array($this->requests) && count($this->requests) > 0) {
            foreach ($this->requests as $fieldKey => $fieldValue) {
                $this->postParams[$fieldKey] = $fieldValue;
                $fields[] = $fieldKey . '=' . $fieldValue;
            }
            if (count($fields) > 0) {
                $this->params = '?' . implode('&', $fields);
            }
        }
    }
}