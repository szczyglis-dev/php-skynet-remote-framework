<?php

/**
 * Skynet/Renderer/SkynetRendererInterface.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Renderer;

/**
 * Skynet Renderer Interface
 *
 * Interface for custom renderer classes.
 */
interface SkynetRendererInterface
{
    /**
     * Returns rendered data
     *
     * @return string output
     */
    public function render();
}