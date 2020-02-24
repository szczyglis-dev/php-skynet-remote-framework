<?php

/**
 * Skynet/State/SkynetState.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\State;

/**
 * Skynet State
 *
 * Stores internal information about state
 */
class SkynetState
{
    /** @var integer Code */
    private $code;

    /** @var string Message */
    private $msg;

    /** @var integer State/connect ID */
    private $stateId;


    /**
     * Constructor
     *
     * @param integer $code State Code
     * @param string $msg State Message
     */
    public function __construct($code, $msg)
    {
        $this->code = $code;
        $this->msg = $msg;
    }

    /**
     * Sets global statesID
     *
     * @param integer $id
     */
    public function setStateId($id)
    {
        $this->stateId = $id;
    }

    /**
     * Sets state code
     *
     * @param integer $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Sets state message
     *
     * @param string $msg
     */
    public function setMsg()
    {
        $this->msg = $msg;
    }

    /**
     * Returns state code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns stateID
     *
     * @return integer
     */
    public function getStateId()
    {
        return $this->stateId;
    }

    /**
     * Returns state message
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * Returns state as string
     *
     * @return string
     */
    public function __toString()
    {
        $id = '';
        if ($this->stateId !== null) $id = '[@' . $this->stateId . '] ';
        return '<b>' . $id . $this->code . '</b>: ' . $this->msg;
    }
}