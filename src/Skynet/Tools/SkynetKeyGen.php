<?php

/**
 * Skynet/Tools/SkynetKeyGen.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Tools;

/**
 * Standalone KEY ID generator
 */
class SkynetKeyGen
{

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Generates key
     *
     * @return string Generated Skynet KEY ID
     */
    public function generate()
    {
        $rand = rand(0, 99999);
        $key = sha1(time() . $rand);
        return $key;
    }

    /**
     * Generates key
     *
     * @param string $mode HTML or CLI
     *
     * @return string Generated Skynet KEY ID
     */
    public function show($mode = 'cli')
    {
        $output = '';
        switch ($mode) {
            case 'html':
                $output = '<h1>' . $this->generate() . '</h1>Place generated key into const <b>KEY_ID</b> in <b>/src/SkynetUser/SkynetConfig.php</b> configuration file.</br>Refresh (F5) page to generate another key.';
                break;


            case 'cli':
                $output = "\n===================\nKEY: " . $this->generate() . "\n===================\nPlace generated key into const KEY_ID into 'src/Skynet/SkynetConfig.php' file.\nRelaunch script to generate another key.";
                break;
        }
        return $output;
    }
}