<?php

/**
 * Skynet/Renderer/Html//SkynetRendererHtmlModeRenderer.php
 *
 * @package Skynet
 * @version 1.1.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.1.0
 */

namespace Skynet\Renderer\Html;

use Skynet\Renderer\SkynetRendererAbstract;

/**
 * Skynet Renderer Mode Renderer
 *
 */
class SkynetRendererHtmlModeRenderer extends SkynetRendererAbstract
{
    /** @var string[] HTML elements of output */
    private $output = [];

    /** @var SkynetRendererHtmlElements HTML Tags generator */
    private $elements;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new SkynetRendererHtmlElements();
    }

    /**
     * Renders and returns mode
     *
     * @return string HTML code
     */
    public function render()
    {
        $ajaxSupport = true;

        $output = [];
        $status = $this->connectionMode;

        $classes = [];
        $classes['idle'] = '';
        $classes['single'] = '';
        $classes['broadcast'] = '';

        switch ($status) {
            case 0:
                $classes['idle'] = ' active';
                break;

            case 1:
                $classes['single'] = ' active';
                break;

            case 2:
                $classes['broadcast'] = ' active';
                break;
        }

        $output[] = '<b>SKYNET MODE:</b> ';

        if ($ajaxSupport) {
            $output[] = '<a href="javascript:skynetControlPanel.load(0, false, \'' . basename($_SERVER['PHP_SELF']) . '\');"><span class="statusIdle' . $classes['idle'] . '">Idle</span></a> ';
            $output[] = '<a href="javascript:skynetControlPanel.load(1, false, \'' . basename($_SERVER['PHP_SELF']) . '\');"><span class="statusSingle' . $classes['single'] . '">Single</span></a> ';
            $output[] = '<a href="javascript:skynetControlPanel.load(2, false, \'' . basename($_SERVER['PHP_SELF']) . '\');"><span class="statusBroadcast' . $classes['broadcast'] . '">Broadcast</span></a>';

        } else {
            $output[] = '<a href="?_skynetSetConnMode=0"><span class="statusIdle' . $classes['idle'] . '">Idle</span></a> ';
            $output[] = '<a href="?_skynetSetConnMode=1"><span class="statusSingle' . $classes['single'] . '">Single</span></a> ';
            $output[] = '<a href="?_skynetSetConnMode=2"><span class="statusBroadcast' . $classes['broadcast'] . '">Broadcast</span></a>';
        }

        return '<div class="modeButtons">' . implode($output) . '</div>';
    }
}