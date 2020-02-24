<?php

/**
 * Skynet/EventListener/SkynetEventListenerFiles.php
 *
 * @package Skynet
 * @version 1.2.1
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
 * Skynet Event Listener - Files
 *
 * Skynet Files Read/Write/Send
 */
class SkynetEventListenerFiles extends SkynetEventListenerAbstract implements SkynetEventListenerInterface
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
            if ($this->request->get('@fput') !== null) {
                $params = $this->request->get('@fput');
                if (isset($params['source'])) {
                    $source = $params['source'];
                    if (!empty($source)) {
                        if (file_exists($source)) {
                            $data = file_get_contents($source);
                            if ($data !== null && $data !== false) {
                                $params['data'] = $data;
                                $this->request->set('@fput', $params);
                            } else {
                                $this->addMonit('[WARNING] @fput: File is NULL or file open error: ' . $source);
                            }

                        } else {
                            $this->addMonit('[WARNING] @fput: File not exists: ' . $source);
                        }

                    } else {
                        $this->addMonit('[WARNING] @fput: File source is empty');
                    }
                }
                //var_dump($params);
            }
        }

        if ($context == 'afterReceive') {

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
            if ($this->response->get('@<<fgetStatus') !== null) {
                $this->addMonit('[STATUS] File get status: ' . $this->response->get('@<<fgetStatus'));
            }

            if ($this->response->get('@<<fputStatus') !== null) {
                $this->addMonit('[STATUS] File put status: ' . $this->response->get('@<<fputStatus'));
            }

            if ($this->response->get('@<<fdelStatus') !== null) {
                $this->addMonit('[STATUS] File delete status: ' . $this->response->get('@<<fdelStatus'));
            }

            if ($this->response->get('@<<mkdirStatus') !== null) {
                $this->addMonit('[STATUS] Directory create: ' . $this->response->get('@<<mkdirStatus'));
            }

            if ($this->response->get('@<<rmdirStatus') !== null) {
                $this->addMonit('[STATUS] Directory delete: ' . $this->response->get('@<<rmdirStatus'));
            }

            if ($this->response->get('@<<lsStatus') !== null) {
                $this->addMonit('[STATUS] Directory listing: ' . $this->response->get('@<<lsStatus'));
            }

            if ($this->response->get('@<<lsOutput') !== null) {
                $this->addMonit($this->response->get('@<<lsOutput'));
            }

            if ($this->response->get('@<<fgetFile') !== null) {
                $this->addMonit('[SUCCESS] Remote file received: ' . $this->response->get('@<<fgetFile'));

                $dir = '_download';
                if (!is_dir($dir)) {
                    if (!@mkdir($dir)) {
                        $this->addError('FGET', 'MKDIR ERROR: ' . $dir);
                        $this->addMonit('[ERROR CREATING DIR] Directory not created: ' . $dir);
                        return false;
                    }
                }

                $fileName = time() . '_' . str_replace(array("\\", "/"), "-", $this->response->get('@<<fgetFile'));
                $file = $dir . '/' . $fileName;
                if (!@file_put_contents($file, $this->response->get('@<<fgetData'))) {
                    $this->addError('FGET', 'FILE SAVE ERROR: ' . $file);
                    $this->addMonit('[ERROR SAVING FILE] Remote file received but not saved: ' . $file);
                } else {
                    $this->addState('FGET', 'FILE SAVED: ' . $file);
                    $this->addMonit('[SUCCESS] Remote file saved: ' . $file);
                }
            }
        }

        if ($context == 'beforeSend') {
            /* File read */
            if ($this->request->get('@fget') !== null) {
                if (!is_array($this->request->get('@fget'))) {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<fgetStatus', $result);
                    return false;
                }

                $params = $this->request->get('@fget');
                if (isset($params['path']) && !empty($params['path'])) {
                    $file = $params['path'];
                } else {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<fgetStatus', $result);
                    return false;
                }

                $result = 'TRYING';

                if (file_exists($file)) {
                    $result = 'FILE EXISTS: ' . $file;
                    $data = @file_get_contents($file);
                    if ($data !== null) {
                        $result = 'FILE READED: ' . $file;
                        $this->response->set('@<<fgetData', $data);
                        $this->response->set('@<<fgetFile', $file);
                    } else {
                        $result = 'NULL DATA OR READ ERROR';
                    }
                } else {
                    $result = 'FILE NOT EXISTS: ' . $file;
                }
                $this->response->set('@<<fgetStatus', $result);
            }

            /* File save */
            if ($this->request->get('@fput') !== null) {
                if (!is_array($this->request->get('@fput'))) {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<fputStatus', $result);
                    return false;
                }

                $params = $this->request->get('@fput');
                if (isset($params['path']) && !empty($params['path'])) {
                    $file = $params['path'];
                } else {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<fputStatus', $result);
                    return false;
                }

                $result = 'TRYING';
                $data = null;
                if (isset($params['data'])) {
                    $data = $params['data'];
                }

                if (@file_put_contents($file, $data)) {
                    $result = 'FILE SAVED: ' . $file;
                } else {
                    $result = 'FILE NOT SAVED: ' . $file;
                }
                $this->response->set('@<<fputStatus', $result);
            }

            /* File delete */
            if ($this->request->get('@fdel') !== null) {
                if (!is_array($this->request->get('@fdel'))) {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<fdelStatus', $result);
                    return false;
                }

                $params = $this->request->get('@fdel');
                if (isset($params['path']) && !empty($params['path'])) {
                    $file = $params['path'];
                } else {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<fdelStatus', $result);
                    return false;
                }

                $result = 'TRYING';
                if (file_exists($file)) {
                    if (@unlink($file)) {
                        $result = 'FILE DELETED: ' . $file;
                    } else {
                        $result = 'FILE NOT DELETED: ' . $file;
                    }
                } else {
                    $result = 'FILE NOT EXISTS: ' . $file;
                }
                $this->response->set('@<<fdelStatus', $result);
            }

            /* Dir create */
            if ($this->request->get('@mkdir') !== null) {
                if (!is_array($this->request->get('@mkdir'))) {
                    $result = 'NO DIRNAME IN PARAM';
                    $this->response->set('@<<mkdirStatus', $result);
                    return false;
                }

                $params = $this->request->get('@mkdir');
                if (isset($params['path']) && !empty($params['path'])) {
                    $dir = $params['path'];
                } else {
                    $result = 'NO DIRNAME IN PARAM';
                    $this->response->set('@<<mkdirStatus', $result);
                    return false;
                }


                $result = 'TRYING';
                if (!is_dir($dir)) {
                    if (mkdir($dir)) {
                        $result = 'DIR CREATED: ' . $dir;
                    } else {
                        $result = 'DIR NOT CREATED: ' . $dir;
                    }
                } else {
                    $result = 'DIR EXISTS: ' . $dir;
                }
                $this->response->set('@<<mkdirStatus', $result);
            }

            /* Directory delete */
            if ($this->request->get('@rmdir') !== null) {
                if (!is_array($this->request->get('@rmdir'))) {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<rmdirStatus', $result);
                    return false;
                }

                $params = $this->request->get('@rmdir');
                if (isset($params['path']) && !empty($params['path'])) {
                    $dir = $params['path'];
                } else {
                    $result = 'NO PATH IN PARAM';
                    $this->response->set('@<<rmdirStatus', $result);
                    return false;
                }

                $result = 'TRYING';
                if (file_exists($dir)) {
                    if ($this->rrmdir($dir)) {
                        $result = 'DIRECTORY DELETED: ' . $dir;
                    } else {
                        $result = 'DIRECTORY NOT DELETED: ' . $dir;
                    }
                } else {
                    $result = 'DIRECTORY NOT EXISTS: ' . $dir;
                }
                $this->response->set('@<<rmdirStatus', $result);
            }

            /* Directory listing */
            if ($this->request->get('@ls') !== null) {
                $path = '';
                if (is_array($this->request->get('@ls')) && isset($this->request->get('@ls')['path'])) {
                    $path = $this->request->get('@ls')['path'];
                }

                if (!empty($path) && substr($path, -1) == '/') {
                    $path = rtrim($path, '/');
                }

                if (!empty($path) && !is_dir($path)) {
                    $result = 'DIRECTORY NOT EXISTS: ' . $path;
                    $this->response->set('@<<lsStatus', $result);
                    return false;
                }

                $pattern = '*';
                if (isset($this->request->get('@ls')['pattern']) && !empty($this->request->get('@ls')['pattern'])) {
                    $pattern = $this->request->get('@ls')['pattern'];
                }

                if (!empty($path)) {
                    $path .= '/';
                }

                $list = [];
                $files = glob($path . $pattern);
                $cFiles = 0;
                $cDirs = 0;

                foreach ($files as $file) {
                    if (is_dir($file)) {
                        $list[] = '[' . str_replace($path, '', $file) . ']';
                        $cDirs++;
                    } else {
                        $list[] = str_replace($path, '', $file);
                        $cFiles++;
                    }
                }
                $this->response->set('@<<lsStatus', 'Directory: ' . $path . ', Pattern: ' . $pattern . ', Files: ' . $cFiles . ', Dirs: ' . $cDirs);
                $this->response->set('@<<lsOutput', $list);
            }
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
        $console[] = ['@ls', ['', 'path:"/path/to"', 'path:"/path/to", pattern:"*"'], ''];
        $console[] = ['@fget', 'path:"/path/to/file"', ''];
        $console[] = ['@fput', ['path:"/path/to/file", data:"data_to_save"', 'path:"/path/to/file", source:"/path/to/file""'], ''];
        $console[] = ['@fdel', 'path:"/path/to/file"', ''];
        $console[] = ['@mkdir', 'path:"/path/to"', ''];
        $console[] = ['@rmdir', 'path:"/path/to"', ''];

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
     * Recursively removes dir
     *
     * @param string $dir
     */
    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        @unlink($dir . "/" . $object);
                    }
                }
            }
            if (@rmdir($dir)) {
                return true;
            }
        }
    }
}