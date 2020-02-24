<?php

/**
 * Skynet/EventListener/SkynetEventListenerSleeper.php
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

use Skynet\Common\SkynetTypes;
use Skynet\Common\SkynetHelper;

/**
 * Skynet Event Listener - Sleeper
 *
 * Skynet Sleep and WakeUp
 */
class SkynetEventListenerSleeper extends SkynetEventListenerAbstract implements SkynetEventListenerInterface
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
        if ($context == 'afterReceive') {
            if ($this->request->get('@sleep') !== null) {
                $key = 'sleep';
                $value = 1;
                $returnSuccess = [];
                $returnError = [];
                if ($this->opt_set($key, $value)) {
                    $returnSuccess[] = $key;
                } else {
                    $returnError[] = $key;
                    $this->addError(SkynetTypes::OPTIONS, 'UPDATE ERROR: ' . $key);
                }

                if (count($returnSuccess) > 0) {
                    $this->response->set('@<<opt_setSuccess', $returnSuccess);
                }

                if (count($returnError) > 0) {
                    $this->response->set('@<<opt_setErrors', $returnError);
                }
            }


            if ($this->request->get('@wakeup') !== null) {
                $key = 'sleep';
                $value = 0;
                $returnSuccess = [];
                $returnError = [];
                if ($this->opt_set($key, $value)) {
                    $returnSuccess[] = $key;
                } else {
                    $returnError[] = $key;
                    $this->addError(SkynetTypes::OPTIONS, 'UPDATE ERROR: ' . $key);
                }

                if (count($returnSuccess) > 0) {
                    $this->response->set('@<<opt_setSuccess', $returnSuccess);
                }

                if (count($returnError) > 0) {
                    $this->response->set('@<<opt_setErrors', $returnError);
                }
            }
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
            if ($this->response->get('@<<opt_setSuccess') === 'sleep') {
                $this->addMonit('[SUCCESS] Sleep/wakeup');
            }
            if ($this->response->get('@<<opt_setErrors') === 'sleep') {
                $this->addMonit('[ERROR] Sleep/wakeup');
            }
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
        /* if sleep */
        if ($this->cli->isCommand('sleep')) {
            $this->opt_set('sleep', 1);
            return '@SLEEPER: cluster sleeped';
        }

        /* if wakeup */
        if ($this->cli->isCommand('wakeup')) {
            $this->opt_set('sleep', 0);
            return '@SLEEPER: cluster woked up';
        }
    }

    /**
     * onConsole Event
     *
     * Actions executes when HTML Console command in input
     * Access to Console: $this->console
     */
    public function onConsole()
    {
        if ($this->console->isConsoleCommand('sleep')) {
            $this->opt_set('sleep', 1);
            return '@SLEEPER: cluster sleeped';
        }

        if ($this->console->isConsoleCommand('wakeup')) {
            $this->opt_set('sleep', 0);
            return '@SLEEPER: cluster woked up';
        }
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

        $cli[] = ['-sleep', '', 'Sleeps this cluster'];
        $cli[] = ['-wakeup', '', 'Wakeup this cluster'];

        $console[] = ['@sleep', ['me', 'cluster address', 'cluster address1, address2 ...'], 'no args=TO ALL'];
        $console[] = ['@wakeup', ['me', 'cluster address', 'cluster address1, address2 ...'], 'no args=TO ALL'];

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