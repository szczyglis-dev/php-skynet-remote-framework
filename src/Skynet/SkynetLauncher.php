<?php

/**
 * Skynet/SkynetLauncher.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet;

use Skynet\Core\Skynet;
use Skynet\Core\SkynetResponder;

/**
 * Skynet Main Launcher
 */
class SkynetLauncher
{
    /** @var Skynet Skynet Sender and controller */
    public $skynet;

    /** @var SkynetResponder Skynet Responder */
    public $skynetService;

    /**
     * Constructor
     *
     * @param bool $startSender Autostarts Skynet Sender
     * @param bool $startResponder Autostarts Skynet Responder
     *
     * @return Skynet
     */
    public function __construct($startSender = false, $startResponder = false)
    {
        $this->skynet = new Skynet($startSender);
        $this->skynetService = new SkynetResponder($startResponder);
    }

    public function __toString()
    {
        return (string)$this->skynet->renderOutput();
    }
}