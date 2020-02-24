<?php

/**
 * Skynet/Filesystem/SkynetDetector.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.1.0
 */

namespace Skynet\Filesystem;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\Secure\SkynetVerifier;
use Skynet\Cluster\SkynetClustersRegistry;
use Skynet\Error\SkynetException;
use Skynet\Common\SkynetTypes;
use Skynet\Common\SkynetHelper;
use SkynetUser\SkynetConfig;

/**
 * Skynet Detector
 *
 * Checks for other clusters in this directory
 */
class SkynetDetector
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var SkynetClustersRegistry ClustersRegistry instance */
    private $clustersRegistry;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->clustersRegistry = new SkynetClustersRegistry();
    }

    /**
     * Detect clusters in folder
     *
     * @return string[] Array of possible clusters
     */
    private function checkDir($dir = '')
    {
        $clusters = [];

        if (!empty($dir)) {
            if (substr($dir, -1) != '/') {
                $dir .= '/';
            }
        }

        $d = glob($dir . '*skynet*.php');
        foreach ($d as $file) {
            $name = str_replace($d, '', $file);
            $address = SkynetHelper::getMyServer() . '/' . $file;

            if (!$this->clustersRegistry->addressExists($address) && $address != SkynetHelper::getMyUrl() && $file != 'skynet_client.php') {
                $clusters[] = $address;
            }
        }
        return $clusters;
    }

    /**
     * Check for clusters in dir
     *
     * @return string monit
     */
    public function check()
    {
        $clusters = $this->checkDir();
        if (count($clusters) > 0) {
            $monit = 'Clusters detector: Possible Skynet clusters detected in this directory: <br>';

            foreach ($clusters as $cluster) {
                $monit .= ' - <a href="javascript:skynetControlPanel.insertConnect(\'' . SkynetConfig::get('core_connection_protocol') . $cluster . '\');"><b>' . $cluster . '</b></a><br>';
            }

            $monit .= 'If you want to try to connect with this address click on address above and send <b>@connect</b> request.';
            return $monit;

        } else {

            return null;
        }
    }
}