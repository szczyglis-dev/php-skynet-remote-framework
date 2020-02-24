<?php

/**
 * Skynet/Data/SkynetParams.php
 *
 * @package Skynet
 * @version 1.1.4
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Data;

use Skynet\Debug\SkynetDebug;

/**
 * Skynet Command
 */
class SkynetParams
{

    /** @var SkynetDebug Debugger */
    private $debugger;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->debugger = new SkynetDebug();
    }

    /**
     * Returns packed params
     *
     * @param mixed[] $params Params array
     *
     * @return string[]
     */
    public function packParams($params)
    {
        if ($params === null || empty($params)) {
            return false;
        }

        if (!is_array($params)) {
            return $params;

        } else {

            if (count($params) == 1) {
                $key = key($params);

                if (!is_array($params[$key]) && is_numeric($key)) {
                    return $params[$key];
                }
            }
        }

        $prefix = '';
        $paramsValues = [];
        $c = count($params);
        if ($c > 0) {
            foreach ($params as $p => $param) {
                if (is_array($param)) {
                    foreach ($param as $k => $v) {
                        /* pack into key:value string */
                        $safeKey = $this->sanitizeVal($k);
                        $safeValue = $this->sanitizeVal($v);
                        $paramsValues[$safeKey] = $safeValue;
                    }

                } else {

                    if (is_numeric($p)) {
                        $paramsValues[$p] = str_replace(';', '', $param);
                    } else {
                        $safeKey = $this->sanitizeVal($p);
                        $safeValue = $this->sanitizeVal($param);
                        $paramsValues[$p] = $safeValue;
                    }
                }
            }
        }

        if ($c > 0) {
            $prefix = '$#';
        }
        return $prefix . serialize($paramsValues);
    }

    /**
     * Returns unpacked params
     *
     * @param mixed $params Packed params string
     *
     * @return string[]
     */
    public function unpackParams($params)
    {
        $params = preg_replace('/^\$#/', '', $params);

        $e = unserialize($params);

        if (count($e) < 1) {
            return $params;
        }
        $fields = [];

        foreach ($e as $element) {
            if (strpos($element, ':') !== false) {
                if (strpos($element, '://') === false) {
                    /* key => val */
                    $parts = explode(':', $element);
                    $key = $parts[0];
                    $val = $this->unsanitizeVal($parts[1]);
                    $fields[] = [$key => $val];
                } else {
                    $val = $this->unsanitizeVal($element);
                    $fields[] = $val;
                }

            } else {
                /* var */
                $val = $element;
                $fields[] = $val;
            }
        }

        //var_dump($fields);
        return $e;
    }

    /**
     * Sanitizes val
     *
     * @param string $input
     *
     * @return string
     */
    private function sanitizeVal($input)
    {
        return str_replace(array('$#'), array('$$1$$'), $input);
    }

    /**
     * Unsanitizes val
     *
     * @param string $input
     *
     * @return string
     */
    private function unsanitizeVal($input)
    {
        return str_replace(array('$$1$$'), array('$#'), $input);
    }

    /**
     * Checks for params is packed
     *
     * @param bool True if packed
     */
    public function isPacked($params)
    {
        if (strpos($params, '$#') === 0) {
            return true;
        }
    }

    public function isInternal($param)
    {
        if (strpos($param, '_') == 0) {
            return true;
        } elseif (strpos($param, '@_') == 0) {
            return true;
        }
    }

    public function translateInternalParam($param)
    {
        $keys = [];

        $keys['skynet'] = 'In Skynet';
        $keys['skynet_chain_new'] = 'New Chain value';
        $keys['skynet_clusters_chain'] = 'Clusters Chain';
        $keys['skynet_id'] = 'Skynet Key ID';
        $keys['skynet_ping'] = 'Ping (microtime)';
        $keys['skynet_hash'] = 'Hash';
        $keys['skynet_chain'] = 'Actual Chain value';
        $keys['skynet_chain_updated_at'] = 'Last update of Chain value';
        $keys['skynet_version'] = 'Skynet Version';
        $keys['skynet_cluster_url'] = 'Cluster address';
        $keys['skynet_cluster_ip'] = 'Cluster IP';
        $keys['skynet_cluster_time'] = 'Time of sent';
        $keys['skynet_clusters'] = 'Clusters chain';
        $keys['skynet_sender_time'] = 'Request sender time';
        $keys['skynet_sender_url'] = 'Request sender address';
        $keys['skynet_checksum'] = 'MD5 checksum';

        $prefix = '';

        $internal = '_' . $param;
        $internalEcho = '@_' . $param;

        if (strpos($param, '_') == 0) {
            $check = preg_replace('/^_/', '', $param);
        } elseif (strpos($param, '@_') == 0) {
            $check = preg_replace('/^@_/', '', $param);
            $prefix = '@>>';
        }

        if (array_key_exists($check, $keys)) {
            return $prefix . $keys[$check];
        } else {
            return $param;
        }
    }
}