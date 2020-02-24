<?php

/**
 * Skynet/Common/SkynetTypes.php
 *
 * @package Skynet
 * @version 1.0.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Common;

/**
 * Skynet Types
 *
 * Defines types constants
 */
class SkynetTypes
{
    /** @var string Global Error */
    const STATUS_OK = 'OK';

    /** @var string Global OK */
    const STATUS_ERR = 'ERROR';

    /** @var string Connection Ok */
    const CONN_OK = 'CONNECTION OK';

    /** @var string Connection Error */
    const CONN_ERR = 'CONNECTION ERROR';

    /** @var string Database Connection OK */
    const DBCONN_OK = 'DB CONNECTION OK';

    /** @var string Database Connection Error */
    const DBCONN_ERR = 'DB CONNECTION ERROR';

    /** @var string Status of CURL */
    const CURL = 'CURL STATUS';

    /** @var string Error */
    const CURL_ERR = 'CURL ERROR';

    /** @var string Self-cloner */
    const CLONER = 'SKYNET CLONER';

    /** @var string Status of file_get_constents() */
    const FILE_GET_CONTENTS = 'FILE_GET_CONTENTS';

    /** @var string PDO Exception */
    const PDO = 'PDO EXCEPTION';

    /** @var string Database status */
    const DB = 'DB';

    /** @var string CHAIN */
    const CHAIN = 'CHAIN';

    /** @var string Status of SkynetVerifier */
    const VERIFY = 'VERIFIER';

    /** @var string Status of SkynetVerifier */
    const HEADER = 'CLUSTER HEADER';

    /** @var string Status of SkynetResponse */
    const RESPONSE = 'RESPONSE';

    /** @var string Status of SkynetRequeste */
    const REQUEST = 'REQUEST';

    /** @var string Status of cluster */
    const CLUSTER = 'CLUSTER';

    /** @var string Status of cluster */
    const CLUSTERS_DB = 'CLUSTERS DB';

    /** @var string Status of file logger */
    const LOGFILE = 'FILE LOGGER';

    /** @var string Status of Emailer */
    const EMAIL = 'EMAIL SENDER';

    /** @var string Status of REGISTRY */
    const REGISTRY = 'REGISTRY';

    /** @var string Status of OPTIONS */
    const OPTIONS = 'OPTIONS';

    /** @var string Skynet Exception */
    const EXCEPTION = 'SKYNET EXCEPTION';

    /** @var string Status of SELF-UPDATER */
    const UPDATER = 'SELF-UPDATER';

    /** @var string Status of TXTLOG */
    const TXT_LOG = 'TXT LOGGER';

    /** @var string Status of TXTLOG */
    const DB_LOG = 'DB LOGGER';

    /** @var string Assignment - response */
    const A_RESPONSE_OK = 'RESPONSE ASSIGNED';

    /** @var string Assignment - request */
    const A_REQUEST_OK = 'REQUEST ASSIGNED';
}