<?php

/**
 * Skynet/Renderer/Html//SkynetRendererHtmlConsoleRenderer.php
 *
 * @package Skynet
 * @version 1.1.2
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Renderer\Html;

use Skynet\Console\SkynetConsole;
use Skynet\SkynetVersion;
use SkynetUser\SkynetConfig;

/**
 * Skynet Renderer HTML Console Renderer
 */
class SkynetRendererHtmlConsoleRenderer
{
    /** @var SkynetRendererHtmlElements HTML Tags generator */
    private $elements;

    /** @var SkynetConsole Console */
    private $console;

    /** @var string[] output from listeners */
    private $listenersOutput = [];


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new SkynetRendererHtmlElements();
        $this->console = new SkynetConsole();
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
     * Assigns output from listeners
     *
     * @param string[] $output
     */
    public function setListenersOutput($output)
    {
        $this->listenersOutput = $output;
    }

    /**
     * Parses params into select input
     *
     * @param mixed[] $params Array of params
     *
     * @return string Parsed params
     */
    private function parseCommandParams($params)
    {
        $paramsParsed = [];
        if (is_array($params) && count($params) > 0) {
            foreach ($params as $param) {
                $paramsParsed[] = $this->elements->getLt() . $param . $this->elements->getGt();
            }
            return implode(' or ', $paramsParsed);
        }
    }

    /**
     * Renders console helpers
     *
     * @return string HTML code
     */
    private function renderConsoleHelpers()
    {
        $options = [];
        $options[] = '<option value="0"> -- commands -- </option>';

        $commands = $this->console->getCommands();

        if (count($commands) > 0) {
            foreach ($commands as $code => $command) {
                $descStr = '';
                if (!empty($command->getHelperDescription())) {
                    $descStr = ' | ' . $command->getHelperDescription();
                }
                $params = $this->parseCommandParams($command->getParams()) . $descStr;
                $options[] = '<option value="' . $code . '">' . $code . ' ' . $params . '</option>';
            }
        }

        return "<select id='cmdsList' onchange='skynetControlPanel.insertCommand();' name='_cmd1'>" . implode('', $options) . "</select>";
    }

    /**
     * Parses input and shows debug
     *
     * @param string $input Console raw Input string
     *
     * @return string HTML code
     */
    private function parseConsoleInputDebug($input)
    {
        $this->console->parseConsoleInput($input);
        $errors = $this->console->getParserErrors();
        $states = $this->console->getParserStates();
        $outputs = [];
        $output = '';

        $parsedErrors = '';
        $parsedStates = '';
        $i = 1;
        if (is_array($errors) && count($errors) > 0) {
            $parsedErrors .= $this->elements->addHeaderRow($this->elements->addSubtitle('Parser errors'));
            foreach ($errors as $error) {
                $parsedErrors .= $this->elements->addValRow('InputParserError #' . $i, $this->elements->addSpan($error, 'error'));
                $i++;
            }
        }

        if (SkynetConfig::get('console_debug')) {
            $i = 1;

            if (is_array($states) && count($states) > 0) {
                $parsedErrors .= $this->elements->addHeaderRow($this->elements->addSubtitle('Parser states'));
                foreach ($states as $state) {
                    $parsedStates .= $this->elements->addValRow('InputParserState #' . $i, $this->elements->addSpan($state, 'yes'));
                    $i++;
                }
            }
        }

        /* Add output from listeners */
        foreach ($this->listenersOutput as $listenerOutput) {
            if (is_string($listenerOutput) && !empty($listenerOutput)) {
                $outputs[] = $listenerOutput;
            } elseif (is_array($listenerOutput) && count($listenerOutput) > 0) {
                foreach ($listenerOutput as $addressListenerOutput) {
                    $outputs[] = $addressListenerOutput;
                }
            }
        }

        $input = str_replace("\r\n", "\n", $input);
        $input = htmlentities($input);
        $input = str_replace("\n", $this->elements->getNl(), $input);

        $input = $this->elements->addHeaderRow($this->elements->addSubtitle('Input query')) . $this->elements->addRow($input);

        if (count($outputs) > 0) {
            $output = implode("\n", $outputs);
            $output = str_replace("\r\n", "\n", $output);
            $output = htmlentities($output);
            $output = $this->elements->addSectionClass('monits') . str_replace("\n", $this->elements->getNl(), $output) . $this->elements->addSectionEnd();
            $output = $this->elements->addHeaderRow($this->elements->addSubtitle('Output')) . $this->elements->addRow($output);
        }

        return $output . $parsedErrors . $parsedStates . $input;
    }

    /**
     * Renders and returns console form
     *
     * @return string HTML code
     */
    public function renderConsole()
    {
        $ajaxSupport = true;

        $onsubmit = '';
        $submit = '<input type="submit" title="Send request commands from console" value="Send request" class="sendBtn" />';
        if ($ajaxSupport) {
            $submit = '<input type="button" onclick="skynetControlPanel.load(1, true, \'' . basename($_SERVER['PHP_SELF']) . '\');" title="Send request commands from console" value="Send request" class="sendBtn" />';
        }

        return '<form method="post" action="#console' . md5(time()) . '" name="_skynetCmdConsole" class="_skynetCmdConsole">
    ' . $submit . $this->renderConsoleHelpers() . ' See ' . $this->elements->addUrl(SkynetVersion::WEBSITE, 'documentation') . ' for information about console usage 
    <textarea autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus name="_skynetCmdConsoleInput" placeholder="&gt;&gt; Console" id="_skynetCmdConsoleInput"></textarea>
    <input type="hidden" name="_skynetCmdCommandSend" value="1" />
    </form>';
    }

    /**
     * Renders sended input
     *
     * @return string HTML code
     */
    public function renderConsoleInput()
    {
        if (isset($_REQUEST['_skynetCmdConsoleInput'])) {
            return $this->parseConsoleInputDebug($_REQUEST['_skynetCmdConsoleInput']);
        } else {
            return $this->elements->addHeaderRow($this->elements->addSubtitle('Console')) . $this->elements->addRow('-- no input --');
        }
    }
}