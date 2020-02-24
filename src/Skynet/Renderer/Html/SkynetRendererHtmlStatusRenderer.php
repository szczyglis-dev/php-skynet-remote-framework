<?php

/**
 * Skynet/Renderer/Html//SkynetRendererHtmlStatusRenderer.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.1.0
 */

namespace Skynet\Renderer\Html;

use Skynet\Renderer\SkynetRendererAbstract;
use Skynet\Debug\SkynetDebug;
use Skynet\Secure\SkynetAuth;
use Skynet\Secure\SkynetVerifier;

/**
 * Skynet Renderer Status Renderer
 *
 */
class SkynetRendererHtmlStatusRenderer extends SkynetRendererAbstract
{
    /** @var string[] HTML elements of output */
    private $output = [];

    /** @var SkynetRendererHtmlElements HTML Tags generator */
    private $elements;

    /** @var SkynetRendererHtmlConsoleRenderer Console Renderer */
    private $summaryRenderer;

    /** @var SkynetRendererHtmlConnectionsRenderer Connections Renderer */
    private $connectionsRenderer;

    /** @var SkynetRendererHtmlModeRenderer Mode Renderer */
    private $modeRenderer;

    /** @var SkynetRendererHtmlClustersRenderer Clusters Renderer */
    private $clustersRenderer;

    /** @var SkynetRendererHtmlConsoleRenderer Console Renderer */
    private $consoleRenderer;

    /** @var SkynetRendererHtmlListenersenderer Event Listeners Renderer */
    private $listenersRenderer;

    /** @var SkynetRendererHtmlDebugParser Debug Parser */
    private $debugParser;

    /** @var SkynetDebug Debugger */
    private $debugger;

    /** @var SkynetAuth Authorization */
    private $auth;

    /** @var SkynetVerifier Verificationn */
    private $verifier;

    /** @var SkynetThemes Themes */
    private $themes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new SkynetRendererHtmlElements();
        $this->summaryRenderer = new  SkynetRendererHtmlSummaryRenderer();
        $this->connectionsRenderer = new  SkynetRendererHtmlConnectionsRenderer();
        $this->modeRenderer = new  SkynetRendererHtmlModeRenderer();
        $this->clustersRenderer = new  SkynetRendererHtmlClustersRenderer();
        $this->debugParser = new SkynetRendererHtmlDebugParser();
        $this->consoleRenderer = new  SkynetRendererHtmlConsoleRenderer();
        $this->listenersRenderer = new  SkynetRendererHtmlListenersRenderer();
        $this->debugger = new SkynetDebug();
        $this->auth = new SkynetAuth();
        $this->verifier = new SkynetVerifier();
        $this->themes = new SkynetRendererHtmlThemes();
    }

    /**
     * Renders monits
     *
     * @return string HTML code
     */
    private function renderMonits()
    {
        $output = [];

        $c = count($this->monits);
        if ($c > 0) {
            $output[] = $this->elements->addSectionClass('monits');
            $output[] = $this->elements->addBold('Information(s):') . $this->elements->getNl();
            foreach ($this->monits as $monit) {
                $output[] = $monit . $this->elements->getNl();
            }
            $output[] = $this->elements->addSectionEnd();
        }
        return implode($output);
    }

    /**
     * Renders tabs
     *
     * @return string HTML code
     */
    private function renderTabs()
    {
        $output = [];
        $output[] = $this->elements->addSectionClass('tabsHeader');
        $output[] = $this->elements->addTabBtn('States (<span class="numStates">' . count($this->statesFields) . '</span>)', 'javascript:skynetControlPanel.switchTab(\'tabStates\');', 'tabStatesBtn active');
        $output[] = $this->elements->addTabBtn('Errors (<span class="numErrors">' . count($this->errorsFields) . '</span>)', 'javascript:skynetControlPanel.switchTab(\'tabErrors\');', 'tabErrorsBtn errors');
        $output[] = $this->elements->addTabBtn('Config (<span class="numConfig">' . count($this->configFields) . '</span>)', 'javascript:skynetControlPanel.switchTab(\'tabConfig\');', 'tabConfigBtn');
        $output[] = $this->elements->addTabBtn('Console (<span class="numConsole">' . count($this->consoleOutput) . '</span>)', 'javascript:skynetControlPanel.switchTab(\'tabConsole\');', 'tabConsoleBtn');
        $output[] = $this->elements->addTabBtn('Debug (<span class="numDebug">' . $this->debugger->countDebug() . '</span>)', 'javascript:skynetControlPanel.switchTab(\'tabDebug\');', 'tabDebugBtn');
        $output[] = $this->elements->addTabBtn('Listeners (<span class="numListeners">' . $this->countListeners() . '</span>)', 'javascript:skynetControlPanel.switchTab(\'tabListeners\');', 'tabListenersBtn');
        $output[] = $this->elements->addSectionEnd();
        return implode($output);
    }

    /**
     * Counts and Renders listeners
     *
     * @return string HTML code
     */
    public function countListeners()
    {
        return $this->listenersRenderer->countListeners() . '/' . $this->listenersRenderer->countLoggers();
    }

    /**
     * Renders errors
     *
     * @return string HTML code
     */
    public function renderErrors($ajax = false)
    {
        /* Center Main : Left Column: errors */
        $errors_class = null;
        if (count($this->errorsFields) > 0) {
            $errors_class = 'error';
        }

        $output = [];
        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('tabErrors');
        }
        $output[] = $this->elements->beginTable('tblErrors');
        $output[] = $this->elements->addHeaderRow($this->elements->addSubtitle('Errors (' . count($this->errorsFields) . ')', $errors_class));
        $output[] = $this->debugParser->parseErrorsFields($this->errorsFields);
        $output[] = $this->elements->endTable();
        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders states
     *
     * @return string HTML code
     */
    public function renderStates($ajax = false)
    {
        $output = [];

        /* Center Main : Left Column: states */
        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('tabStates');
        }
        $output[] = $this->elements->beginTable('tblStates');
        $output[] = $this->elements->addHeaderRow($this->elements->addSubtitle('States (' . count($this->statesFields) . ')'));
        $output[] = $this->renderMonits();
        $output[] = $this->elements->addHeaderRow2('Sender', 'State');
        $output[] = $this->debugParser->parseStatesFields($this->statesFields);
        $output[] = $this->elements->endTable();
        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders listeners debug
     *
     * @return string HTML code
     */
    public function renderListeners($ajax = false)
    {
        $output = [];

        /* Center Main : Left Column: states */
        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('tabListeners');
        }

        $output[] = $this->listenersRenderer->render();

        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders debug
     *
     * @return string HTML code
     */
    public function renderDebug($ajax = false)
    {
        $output = [];

        /* Center Main : Left Column: states */
        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('tabDebug');
        }

        $output[] = $this->elements->beginTable('tblStates');
        $output[] = $this->elements->addHeaderRow($this->elements->addSubtitle('Debugger (' . $this->debugger->countDebug() . ')'));
        $output[] = $this->debugParser->parseDebugFields($this->debugger->getData());
        $output[] = $this->elements->endTable();
        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders config
     *
     * @return string HTML code
     */
    public function renderConfig($ajax = false)
    {
        $output = [];

        /* Center Main : Left Column: Config */
        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('tabConfig');
        }
        $output[] = $this->elements->beginTable('tblConfig');
        $output[] = $this->elements->addHeaderRow($this->elements->addSubtitle('Config (' . count($this->configFields) . ')'), 3);
        $output[] = $this->elements->addHeaderRow3('Option', 'Value', 'Key');
        $output[] = $this->debugParser->parseConfigFields($this->configFields);
        $output[] = $this->elements->endTable();
        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders errors
     *
     * @return string HTML code
     */
    public function renderConsoleDebug($ajax = false)
    {
        $output = [];
        $this->consoleRenderer->setListenersOutput($this->consoleOutput);

        /* If console input */
        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('tabConsole');
        }

        $output[] = $this->elements->addSectionId('consoleDebug');
        $output[] = $this->elements->beginTable('tblConfig');
        $output[] = $this->consoleRenderer->renderConsoleInput();
        $output[] = $this->elements->endTable();
        $output[] = $this->elements->addSectionEnd();

        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders warns
     *
     * @return string HTML code
     */
    public function renderWarnings($ajax = false)
    {
        $output = [];

        /* Empty password warning */
        if (!$this->auth->isPasswordGenerated()) {
            $output[] = $this->elements->addBold('SECURITY WARNING: ', 'error') . $this->elements->addSpan('Access password is not set yet. Use [pwdgen.php] to generate your password and place generated password into [/src/SkynetUser/SkynetConfig.php]', 'error') . $this->elements->getNl() . $this->elements->getNl();
        }

        /* Default ID warning */
        if (!$this->verifier->isKeyGenerated()) {
            $output[] = $this->elements->addBold('SECURITY WARNING: ', 'error') . $this->elements->addSpan('Skynet ID KEY is empty or set to default value. Use [keygen.php] to generate new random ID KEY and place generated key into [/src/SkynetUser/SkynetConfig.php]', 'error');
        }

        return implode('', $output);
    }

    /**
     * Renders mode
     *
     * @return string HTML code
     */
    private function renderMode()
    {
        $output = [];

        $output[] = $this->elements->addSectionClass('innerMode panel');
        $output[] = $this->elements->addSectionClass('hdrConnection');
        $output[] = $this->modeRenderer->render();
        $output[] = $this->elements->addSectionEnd();
        $output[] = $this->elements->addSectionEnd();
        $output[] = $this->renderOptions();

        return implode('', $output);
    }

    /**
     * Renders warns
     *
     * @return string HTML code
     */
    public function renderClusters($ajax = false)
    {
        $this->clustersRenderer->setClustersData($this->clustersData);
        $output = [];

        if (!$ajax) {
            $output[] = $this->elements->addSectionClass('innerAddresses panel');
        }
        $output[] = $this->elements->beginTable('tblClusters');
        $output[] = $this->clustersRenderer->render();
        $output[] = $this->elements->endTable();
        if (!$ajax) {
            $output[] = $this->elements->addSectionEnd();
        }

        return implode($output);
    }

    /**
     * Renders warns
     *
     * @return string HTML code
     */
    private function renderConsole()
    {
        $output = [];

        $output[] = $this->elements->addSectionClass('sectionConsole');
        $output[] = $this->consoleRenderer->renderConsole();
        $output[] = $this->elements->addSectionEnd();

        return implode($output);
    }

    /**
     * Renders theme switcher
     *
     * @return string HTML code
     */
    private function renderThemeSwitcher()
    {
        $themes = $this->themes->getAvailableThemes();
        $options = [];

        foreach ($themes as $k => $v) {
            if (isset($_SESSION['_skynetOptions']['theme']) && $_SESSION['_skynetOptions']['theme'] == $k) {
                $options[] = '<option value="' . $k . '" selected>' . $v . '</option>';
            } else {
                $options[] = '<option value="' . $k . '">' . $v . '</option>';
            }
        }
        return '<select onchange="skynetControlPanel.changeTheme(this)" name="_skynetTheme">' . implode('', $options) . '</select> ';
    }

    /**
     * Renders and returns logout link
     *
     * @return string HTML code
     */
    public function renderLogoutLink()
    {
        if ($this->auth->isPasswordGenerated()) {
            return $this->elements->addUrl('?_skynetLogout=1', $this->elements->addBold('LOGOUT'), false, 'aLogout');
        }
    }

    public function renderOptions()
    {
        $output = [];
        $output[] = '<form method="get" action="" class="formViews" id="_skynetThemeForm">';
        $output[] = '<input type="hidden" name="_skynetView" value="' . $this->mode . '">';
        $output[] = $this->renderLogoutLink();
        $output[] = ' Theme: ' . $this->renderThemeSwitcher();
        $output[] = '</form>';
        return implode('', $output);
    }

    /**
     * Renders and returns debug section
     *
     * @return string HTML code
     */
    public function render()
    {
        $this->modeRenderer->setConnectionMode($this->connectionMode);
        $this->clustersRenderer->setClustersData($this->clustersData);

        $output = [];

        /* Center Main : Left Column */
        $output[] = $this->elements->addSectionClass('columnDebug');

        $output[] = $this->elements->addSectionClass('sectionStatus');

        $output[] = $this->elements->addSectionClass('sectionAddresses');
        $output[] = $this->renderMode();
        $output[] = $this->renderClusters();
        $output[] = $this->elements->addSectionEnd();


        $output[] = $this->elements->addSectionClass('sectionStates');

        $output[] = $this->elements->addSectionClass('innerStates panel');
        $output[] = $this->renderWarnings();
        $output[] = $this->renderTabs();
        $output[] = $this->renderErrors();
        $output[] = $this->renderConsoleDebug();
        $output[] = $this->renderStates();
        $output[] = $this->renderConfig();
        $output[] = $this->renderDebug();
        $output[] = $this->renderListeners();
        $output[] = $this->elements->addSectionEnd();

        /* end sectionStates */
        $output[] = $this->elements->addSectionEnd();

        $output[] = $this->elements->addClr();

        /* end sectionStatus */
        $output[] = $this->elements->addSectionEnd();

        $output[] = $this->renderConsole();

        /* Center Main : Left Column: END */
        $output[] = $this->elements->addSectionEnd();

        return implode('', $output);
    }
}