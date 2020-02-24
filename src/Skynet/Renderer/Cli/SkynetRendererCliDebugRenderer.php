<?php

/**
 * Skynet/Renderer/Cli/SkynetRendererCliDebugRenderer.php
 *
 * @package Skynet
 * @version 1.1.5
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Renderer\Cli;

use Skynet\Common\SkynetHelper;
use SkynetUser\SkynetConfig;

/**
 * Skynet Renderer Debug Renderer
 *
 */
class SkynetRendererCliDebugRenderer
{
    /** @var SkynetRendererHtmlElements HTML Tags generator */
    private $elements;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new SkynetRendererCliElements();
    }

    /**
     * Assigns Elements Generator
     *
     * @param SkynetRendererHtmlElements $elements
     */
    public function assignElements($elements)
    {
        $this->elements = $elements;
    }

    /**
     * Parses assigned custom fields
     *
     * @param SkynetField[] $fields
     *
     * @return string HTML code
     */
    public function parseFields($fields)
    {
        $rows = [];
        foreach ($fields as $field) {
            $val = $field->getValue();
            if ($field->getName() == 'Sleeped') {
                if ($val == 1) {
                    $val = 'YES';
                } else {
                    $val = 'NO';
                }
            }
            $rows[] = $this->elements->addBold($field->getName() . ':') . ' ' . $val;
        }
        return implode($this->elements->getNl(), $rows);
    }

    /**
     * Parses assigned states data
     *
     * @param SkynetField[] $fields
     *
     * @return string HTML code
     */
    public function parseStatesFields($fields)
    {
        $rows = [];
        foreach ($fields as $field) {
            $val = $field->getValue();
            $rows[] = $this->elements->addBold($field->getName() . ':') . ' ' . $val;
        }
        return implode($this->elements->getNl(), $rows);
    }

    /**
     * Parses assigned errors data
     *
     * @param SkynetField[] $fields
     *
     * @return string HTML code
     */
    public function parseErrorsFields($fields)
    {
        $rows = [];
        foreach ($fields as $field) {
            $errorData = $field->getValue();
            $errorMsg = $errorData[0];
            $errorException = $errorData[1];

            $ex = '';
            if ($errorException !== null && SkynetConfig::get('debug_exceptions')) {
                $ex = $this->elements->addSpan($this->elements->getNl() .
                    $this->elements->addBold('Exception: ') . $errorException->getMessage() . $this->elements->getNl() .
                    $this->elements->addBold('File: ') . $errorException->getFile() . $this->elements->getNl() .
                    $this->elements->addBold('Line: ') . $errorException->getLine() . $this->elements->getNl() .
                    $this->elements->addBold('Trace: ') . str_replace('#', $this->elements->getNl() . '#', $errorException->getTraceAsString()), 'exception');
            }
            $rows[] = $this->elements->addBold($field->getName() . ':', 'error') . ' ' . $this->elements->addSpan($errorData[0], 'error') . $ex;
        }
        if (count($rows) == 0) {
            return '-- no errors --';
        }
        return implode($this->elements->getNl(), $rows);
    }

    /**
     * Parses config value
     *
     * @param mixed $value
     *
     * @return string HTML code
     */
    public function parseConfigValue($value)
    {
        $parsed = $value;
        if (is_bool($value)) {
            if ($value == true) {
                $parsed = $this->elements->addSpan('YES', 'yes');
            } else {
                $parsed = $this->elements->addSpan('NO', 'no');
            }
        }
        return $parsed;
    }

    /**
     * Parses assigned config data
     *
     * @param SkynetField[] $fields
     *
     * @return string HTML code
     */
    public function parseConfigFields($fields)
    {
        $rows = [];
        foreach ($fields as $field) {
            $key = $field->getName();
            if (SkynetConfig::get('translator_config')) {
                $keyTitle = SkynetHelper::translateCfgKey($key);
            } else {
                $keyTitle = $key;
            }
            $rows[] = $this->elements->addBold('[' . $keyTitle . '] ') . $this->parseConfigValue($field->getValue());
        }

        return implode($this->elements->getNl(), $rows);
    }
}