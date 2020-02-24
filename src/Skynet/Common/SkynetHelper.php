<?php

/**
 * Skynet/Common/SkynetHelper.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace Skynet\Common;

use SkynetUser\SkynetConfig;

/**
 * Skynet Helper
 *
 * Common used methods
 */
class SkynetHelper
{
    /**
     * Returns cluster address by host or ip
     *
     * @return string
     */
    public static function getServerAddress()
    {
        if (SkynetConfig::get('core_connection_mode') == 'host') {
            return self::getServerHost();
        } elseif (SkynetConfig::get('core_connection_mode') == 'ip') {
            return self::getServerIp();
        }
    }

    /**
     * Returns cluster host
     *
     * @return string
     */
    public static function getServerHost()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }
    }

    /**
     * Returns basename
     *
     * @return string
     */
    public static function getMyBasename()
    {
        return basename($_SERVER['PHP_SELF']);
    }

    /**
     * Returns cluster IP address
     *
     * @return string
     */
    public static function getServerIp()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }
    }

    /**
     * Returns remote client IP address
     *
     * @return string
     */
    public static function getRemoteIp()
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return '-';
        }

        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        return $ip;
    }

    /**
     * Returns cluster filename
     *
     * @return string
     */
    public static function getMyself()
    {
        return $_SERVER['PHP_SELF'];
    }

    /**
     * Returns cluster adress to
     *
     * @return string
     */
    public static function getMyServer()
    {
        return self::getServerAddress() . pathinfo($_SERVER['PHP_SELF'], PATHINFO_DIRNAME);
    }

    /**
     * Returns cluster full address
     *
     * @return string
     */
    public static function getMyUrl()
    {
        return self::getServerAddress() . self::getMyself();
    }

    /**
     * Returns cluster full address by IP
     *
     * @return string
     */
    public static function getMyUrlByIp()
    {
        return self::getServerIp() . self::getMyself();
    }

    /**
     * Validates URL
     *
     * @param string $url
     *
     * @return bool
     */
    public function isUrl($url)
    {
        if (preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url)) {
            return true;
        }
    }

    /**
     * Sanitazes URL
     *
     * @param string $url
     *
     * @return string
     */
    public static function cleanUrl($url)
    {
        return str_replace(array(SkynetConfig::get('core_connection_protocol'), 'http://', 'https://'), '', $url);
    }

    /**
     * Translates Config value
     *
     * @param string $key
     *
     * @return string
     */
    public static function translateCfgKey($key)
    {
        $titles = [];

        $titles['core_secure'] = 'Secure connections by Key';
        $titles['core_raw'] = 'Disable data encryption';
        $titles['core_updater'] = 'Updater engine enabled';
        $titles['core_cloner'] = 'Cloner engine enabled';
        $titles['core_check_new_versions'] = 'Check GitHub for new version';
        $titles['core_urls_chain'] = 'URLs Chain Engine enabled';
        $titles['core_mode'] = 'Default mode (0=Idle, 1=Single, 2=Broadcast)';
        $titles['core_encryptor'] = 'Data encryptor';
        $titles['core_encryptor_algorithm'] = 'Data encryptor algorithm';
        $titles['core_renderer_theme'] = 'Renderer theme';
        $titles['core_date_format'] = 'Date format';
        $titles['core_admin_ip_whitelist'] = 'IP\'s whitelist for access to Control Panel';
        $titles['core_open_sender'] = 'Always send requests (even without login to Control Panel)';

        $titles['client_registry'] = 'Use Clusters Registry on Client';
        $titles['client_registry_responder'] = 'Use Clusters Registry when connection from Client';

        $titles['translator_config'] = 'Translate config keys to titles';
        $titles['translator_params'] = 'Translate internal params to titles';

        $titles['core_connection_mode'] = 'Connection by host or IP';
        $titles['core_connection_type'] = 'Connection provider';
        $titles['core_connection_protocol'] = 'Connection protocol';
        $titles['core_connection_ssl_verify'] = 'Verify SSL if https connection';
        $titles['core_connection_curl_output'] = 'Output full CURL data in CLI';

        $titles['core_connection_ip_whitelist'] = 'IP Whitelist (accepts requests only from list)';

        $titles['emailer_responses'] = 'Log responses via email';
        $titles['emailer_requests'] = 'Log requests via email';
        $titles['emailer_email_address'] = 'Emails receiver address';

        $titles['response_include_request'] = 'Include @request in response';

        $titles['logs_errors_with_full_trace'] = 'Log errors with full trace';
        $titles['logs_txt_include_secure_data'] = 'Include Key ID and Hash in txt logs';
        $titles['logs_txt_include_clusters_data'] = 'Include clusters URLs in txt logs';

        $titles['logs_dir'] = 'Directory for logs';

        $titles['logs_txt_access_errors'] = 'Log access errors in .txt files';
        $titles['logs_txt_errors'] = 'Log core errors in .txt files';
        $titles['logs_txt_requests'] = 'Log requests in .txt files';
        $titles['logs_txt_responses'] = 'Log responses in .txt files';
        $titles['logs_txt_echo'] = 'Log @echo in .txt files';
        $titles['logs_txt_broadcast'] = 'Log @broadcast in .txt files';
        $titles['logs_txt_selfupdate'] = 'Log @self_update in .txt files';

        $titles['logs_txt_include_internal_data'] = 'Include internal (_skynet_*)  params in .txt logs';

        $titles['logs_db_access_errors'] = 'Log access errors in database';
        $titles['logs_db_errors'] = 'Log core errors in database';
        $titles['logs_db_requests'] = 'Log requests in database';
        $titles['logs_db_responses'] = 'Log responses in database';
        $titles['logs_db_echo'] = 'Log @echo in database';
        $titles['logs_db_broadcast'] = 'Log @broadcast in database';
        $titles['logs_db_selfupdate'] = 'Log @self_update in database';

        $titles['logs_db_include_internal_data'] = 'Include internal (_skynet_*)  params in database';

        $titles['db'] = 'Database support enabled';
        $titles['db_type'] = 'Database type (PDO)';
        $titles['db_host'] = 'Database host';
        $titles['db_user'] = 'Database username';
        $titles['db_password'] = 'Database password';
        $titles['db_dbname'] = 'Database name';
        $titles['db_encoding'] = 'Database encoding';
        $titles['db_port'] = 'Database port';

        $titles['db_file'] = 'Database file (for SQLite)';
        $titles['db_file_dir'] = 'Database files directory (for SQLite)';

        $titles['console_debug'] = 'Enable console commands debugger';
        $titles['debug_exceptions'] = 'Debug errors with full exceptions';
        $titles['debug_internal'] = 'Debug internal skynet params';
        $titles['debug_echo'] = 'Debug internal @echo skynet params';
        $titles['debug_key'] = 'Show KEY ID in Control Panel';

        if (array_key_exists($key, $titles)) {
            return $titles[$key];
        } else {
            return $key;
        }
    }
}