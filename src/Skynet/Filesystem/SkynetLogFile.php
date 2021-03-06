<?php

/**
 * Skynet/Filesystem/SkynetLogFile.php
 *
 * Checking and veryfing access to skynet
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Filesystem;

use Skynet\Error\SkynetErrorsTrait;
use Skynet\State\SkynetStatesTrait;
use Skynet\Common\SkynetTypes;
use Skynet\Common\SkynetHelper;
use Skynet\Error\SkynetException;
use SkynetUser\SkynetConfig;

/**
 * Skynet Log File
 *
 * Creates and manipulates log files
 */
class SkynetLogFile
{
    use SkynetErrorsTrait, SkynetStatesTrait;

    /** @var string[] Array of rows/lines in log */
    private $lines = [];

    /** @var string[] Array of header lines */
    private $headers = [];

    /** @var string[] Array of user-header lines */
    private $dataHeaders = [];

    /** @var string Parsed lines */
    private $data;

    /** @var string Log filename */
    private $fileName;

    /** @var string Name of log */
    private $name;

    /** @var string Extension of log file */
    private $ext;

    /** @var integer File number, numering for multiple files from one generator */
    private $counter;

    /** @var string suffix with name of generator */
    private $selfSuffix;

    /** @var bool Add time prefix */
    private $timePrefix = true;

    /**
     * Constructor
     *
     * @param string $name Name of log
     */
    public function __construct($name = null)
    {
        $this->name = $name;
        $this->ext = '.txt';

        $logsDir = SkynetConfig::get('logs_dir');
        if (!empty($logsDir) && substr($logsDir, -1) != '/') {
            $logsDir .= '/';
            SkynetConfig::set('logs_dir', $logsDir);
        }

        if (!empty($logsDir) && !is_dir($logsDir)) {
            try {
                if (!mkdir($logsDir)) {
                    throw new SkynetException('ERROR CREATING DIRECTORY: ' . $logsDir);
                }

                @file_put_contents($logsDir . 'index.php', '');
                @file_put_contents($logsDir . '.htaccess', 'Options -Indexes');
            } catch (SkynetException $e) {
                $this->addError(SkynetTypes::LOGFILE, 'LOGS DIR: ' . $e->getMessage(), $e);
            }
        }
    }

    /**
     * Adds new line
     *
     * @param string $line
     */
    public function addLine($line = '')
    {
        $this->lines[] = $line;
    }

    /**
     * Sets filename
     *
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Sets suffix
     *
     * @param string $suffix
     */
    public function setSuffix($suffix)
    {
        $this->selfSuffix = $suffix;
    }

    /**
     * Sets auto adding time_prefix
     *
     * @param bool $mode
     */
    public function setTimePrefix($mode)
    {
        $this->timePrefix = $mode;
    }

    /**
     * Sets file counter
     *
     * @param int $counter
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;
    }

    /**
     * Sets log name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Adds header line
     *
     * @param string $data
     */
    public function setHeader($data)
    {
        $this->dataHeaders[] = $data;
    }

    /**
     * Prepares header
     */
    private function addHeader()
    {
        $this->headers[] = implode($this->nl(), $this->dataHeaders);
        $this->headers[] = $this->nl();
        $this->headers[] = $this->lineSeparator();
        $this->headers[] = $this->nl();
    }

    /**
     * Prepares metadata
     */
    private function addMeta()
    {
        $this->headers[] = "Skynet Log file (generated: " . date('H:i:s d.m.Y') . " [" . time() . "])";
        $this->headers[] = $this->nl();
        $this->headers[] = "File generated by: " . SkynetHelper::getMyUrl();
        $this->headers[] = $this->nl();
    }

    /**
     * Returns line separator string
     *
     * @return string
     */
    private function lineSeparator()
    {
        return "------------";
    }

    /**
     * Adds line separator
     */
    public function addSeparator()
    {
        $this->lines[] = $this->lineSeparator();
    }

    /**
     * Returns new line string
     *
     * @return string
     */
    public function nl()
    {
        return "\r\n";
    }

    /**
     * Adds begin tag
     */
    private function addBegin()
    {
        $this->headers[] = " {" . $this->nl();
    }

    /**
     * Adds ending tag
     */
    private function addEnd()
    {
        $this->lines[] = "}";
    }

    /**
     * Adds prefix (log name)
     */
    private function addPrefix()
    {
        $this->headers[] = '#' . $this->name;
    }

    /**
     * Prepares headers
     */
    private function generateHeaders()
    {
        $this->addPrefix();
        $this->addBegin();
        $this->addMeta();
        $this->addHeader();
        $this->data = implode('', $this->headers);
    }

    /**
     * Prepares lines data
     *
     * @param string|null $mode If null then adds the end prefix
     */
    private function generateData($mode = null)
    {
        if ($mode === null) {
            $this->addEnd();
        }
        $this->data .= implode($this->nl(), $this->lines);
    }

    /**
     * Saves log to file
     *
     * @param string|null $mode NULL if new/or overwrite file if exists, "after" - to append new data after old data, or "before" to add data at the beginning of old data
     * @param bool $toFile Save to file
     */
    public function save($mode = null, $toFile = true)
    {
        $logsDir = SkynetConfig::get('logs_dir');

        if ($this->selfSuffix === null) {
            $this->selfSuffix = str_replace("/", "-", SkynetHelper::getMyUrl());
        }
        $suffix = '_' . $this->selfSuffix;

        $counter = '';
        if ($this->counter !== null) {
            $counter = '_' . $this->counter;
        }

        $time = '';
        if ($this->timePrefix) {
            $time = time() . '_';
        }
        $file = $logsDir . $time . $this->fileName . $suffix . $counter . $this->ext;

        /* Save mode */
        if ($mode !== null) {
            $oldData = '';
            if (file_exists($file)) {
                $oldData = @file_get_contents($file);
            }

            if (!empty($oldData)) {
                switch ($mode) {
                    case 'before':
                        $this->generateData($mode);
                        $this->data = $this->data . $this->nl() . $oldData;
                        break;

                    case 'after':
                        $this->generateData($mode);
                        $this->data = $oldData . $this->nl() . $this->data;
                        break;
                }
            } else {
                $this->generateHeaders();
                $this->generateData($mode);
            }

        } else {
            $this->generateHeaders();
            $this->generateData($mode);
        }

        if ($toFile) {
            try {
                if (file_put_contents($file, $this->data)) {
                    $this->addState(SkynetTypes::LOGFILE, $this->name . ' LOG [' . $file . '] SAVED');
                    return true;
                } else {
                    $this->addState(SkynetTypes::LOGFILE, $this->name . ' LOG [' . $file . '] NOT SAVED');
                    throw new SkynetException('ERROR SAVING LOG FILE: ' . $file);
                }
            } catch (SkynetException $e) {
                $this->addError(SkynetTypes::LOGFILE, 'LOG FILE: ' . $e->getMessage(), $e);
            }
        }
        return $this->data;
    }
}