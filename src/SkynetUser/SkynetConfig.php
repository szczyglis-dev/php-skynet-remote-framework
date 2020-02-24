<?php

/**
 * Skynet/SkynetConfig.php
 *
 * @package Skynet
 * @version 1.2.0
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-skynet-remote-framework
 * @copyright 2017 Marcin Szczyglinski
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 1.0.0
 */

namespace SkynetUser;

/**
 * Skynet Config
 *
 * Global configuration of Skynet
 */
class SkynetConfig
{

    /** @var string SKYNET KEY ID, default: 1234567890 */
    const KEY_ID = '1234567890';

    /** @var string SKYNET PASSWORD, default: empty */
    const PASSWORD = '';


    /** @var string[] Array of configuration options */
    private static $config = [

        /*
          ==================================
          A) Core configuration - base options:
          ==================================
        */
        /* core_secure -> bool:[true|false] <- default: true
        If TRUE, Skynet will verify KEY ID in every response, if FALSE - you will able to connect without key (USE THIS ONLY FOR TESTS!!!) */
        'core_secure' => true,

        /* core_raw -> bool:[true|false] <- default: false
        If TRUE all sending and receiving data will be encrypted, if FALSE - all data will be send in plain text */
        'core_raw' => false,

        /* core_updater -> bool:[true|false] <- efault: false
        If TRUE Skynet will enable self-remote-update engine, if FALSE - self-remote-engine will be disabled */
        'core_updater' => true,

        /* core_cloner -> bool:[true|false] <- default: true
        If TRUE - cloner will be enabled and listening for clone command */
        'core_cloner' => false,

        /* core_check_new_versions -> bool:[true|false] <- default: true
        If TRUE - information about new version is given from GitHub */
        'core_check_new_versions' => true,

        /* core_urls_chain -> bool:[true|false] <- default: true
        If TRUE - Skynet will include urls chain to requests/responses and will be updates new clusters from it  */
        'core_urls_chain' => true,

        /* core_mode -> integer:0|1|2 <- default: 2
        Default Skynet Mode. 0 = Idle, 1 = Single, 2 = Broadcast */
        'core_mode' => 2,

        /* core_encryptor -> string:[openSSL|mcrypt|base64|...] <- default: 'openSSL'
        Name of registered class used for encrypting data */
        'core_encryptor' => 'openSSL',

        /* core_encryptor_algorithm -> string <- default: 'aes-256-ctr'
        Algorithm for OpenSSL encryption */
        'core_encryptor_algorithm' => 'aes-256-ctr',

        /* core_renderer_theme -> string:[dark|light|raw|...] <- default: 'dark'
        Theme CSS for HTML Renderer */
        'core_renderer_theme' => 'dark',

        /* core_date_format -> string <- default: 'H:i:s d.m.Y'[
        Date format for date() function */
        'core_date_format' => 'H:i:s d.m.Y',

        /* core_admin_ip_whitelist -> string[] <- default: []
        IP Whitelist for accepting access to Control Panel */
        'core_admin_ip_whitelist' => [],

        /*core_open_sender -> bool:[true|false] <- default: false
        If TRUE Skynet will always sends requests when open (without login to Control Panel) */
        'core_open_sender' => false,

        /*
          ==================================
          B) Client configuration - base options:
          ==================================
        */
        /* core_registry -> bool:[true|false] <- default: false
        If TRUE, Skynet Client will store clusters in registry */
        'client_registry' => false,

        /* core_registry_responder -> bool:[true|false] <- default: false
        If TRUE, Skynet Responder will save cluster when receive connection from client */
        'client_registry_responder' => false,

        /*
          ==================================
          C) Translate configuration
          ==================================
        */

        /* translator_config -> bool:[true|false] <- default: true
        If TRUE - config fields are translated*/
        'translator_config' => true,

        /* translator_params -> bool:[true|false] <- default: true
        If TRUE - param fields are translated*/
        'translator_params' => true,


        /*
          ==================================
          D) Core configuration - connections with clusters:
          ==================================
        */
        /* core_connection_mode -> string:[host|ip] <- default: 'host'
        Specified connection addresses by host or IP */
        'core_connection_mode' => 'host',

        /* core_connection_type -> string:[curl|file_get_contents|...] <- default: 'curl'
        Name of registered class used for connection with clusters */
        'core_connection_type' => 'curl',

        /* core_connection_protocol -> string:[http|https] <- default: 'http://'
        Connections protocol */
        'core_connection_protocol' => 'http://',

        /* core_connection_ssl_verify -> bool:[true|false] <- default: false
        Only for cURL, set to FALSE to disable verification of SSL certificates */
        'core_connection_ssl_verify' => false,

        /* core_connection_curl_cli_echo -> bool:[true|false] <- default: false
        If true CURL will display connection output in CLI mode (VERBOSE OPTION) */
        'core_connection_curl_output' => false,

        /* core_connection_ip_whitelist -> string[] <- default: []
        IP Whitelist for accepting requests from, if empty then all IP's has access to response */
        'core_connection_ip_whitelist' => [],


        /*
          ==================================
          E) Emailer configuration:
          ==================================
        */
        /* core_email_send -> bool:[true|false] <- default: true
        TRUE for enable auto-emailer engine for responses, FALSE to disable */
        'emailer_responses' => true,

        /* core_email_send -> bool:[true|false] <- default: true
        TRUE for enable auto-emailer engine for requests, FALSE to disable */
        'emailer_requests' => true,

        /* core_email_address -> [email] <- default: 'your@email.com'
        Specify email address for receiving emails from Skynet */
        'emailer_email_address' => 'your@email.com',


        /*
          ==================================
          F) Response:
          ==================================
        */
        /* response_include_request -> bool:[true|false] <- default: true
        If TRUE, response will be attaching requests data with @ prefix, if FALSE requests data will not be included into response */
        'response_include_request' => true,


        /*
          ==================================
          G) Logs:
          ==================================
        */
        /* logs_errors_with_full_trace -> bool:[true|false] <- default: true
       Set TRUE to log errors with full error code, file, line and trace data, set FALSE to log only error messages */
        'logs_errors_with_full_trace' => true,

        /* logs_txt_include_secure_data -> bool:[true|false] <- default: true
        Set TRUE to log Skynet Key ID and Hash (use this only for debug, not in production */
        'logs_txt_include_secure_data' => true,

        /* logs_txt_include_clusters_data -> bool:[true|false] <- default: true
        Set TRUE to log clusters URLs and clusters chain (use this only for debug, not in production */
        'logs_txt_include_clusters_data' => true,

        /* logs_dir -> string:[path/] <- default: 'logs/'
        Specify path to dir where Skynet will save logs, or leave empty to save logs in Skynet directory */
        'logs_dir' => 'logs/',

        /* logs_txt_* -> bool:[true|false] <- default: true
        Enable or disable txt logs for specified Event */
        'logs_txt_access_errors' => true,
        'logs_txt_errors' => true,
        'logs_txt_requests' => true,
        'logs_txt_responses' => true,
        'logs_txt_echo' => true,
        'logs_txt_broadcast' => true,
        'logs_txt_selfupdate' => true,

        /* logs_txt_include_internal_data -> bool:[true|false] <-
        If TRUE, Skynet will include internal params in txt logs */
        'logs_txt_include_internal_data' => true,


        /* logs_db_* -> bool:[true|false] <- default: true
        Enable or disable database logs for specified Event */
        'logs_db_access_errors' => true,
        'logs_db_errors' => true,
        'logs_db_requests' => true,
        'logs_db_responses' => true,
        'logs_db_echo' => true,
        'logs_db_broadcast' => true,
        'logs_db_selfupdate' => true,

        /* logs_db_include_internal_data -> bool:[true|false] <- default: false
        If TRUE, Skynet will include internal params in database logs */
        'logs_db_include_internal_data' => false,


        /*
          ==================================
          H) Database configuration:
          ==================================
        */
        /* db -> bool:[true|false] <- default: true
        Enable or disable database support. If disabled some of functions of Skynet will not work  */
        'db' => true,

        /* db_type -> string:[dsn] <- default: 'sqlite'
        Specify adapter for PDO (sqlite is recommended)  */
        'db_type' => 'sqlite',

        /* DB connection config  */
        'db_host' => '127.0.0.1',
        'db_user' => 'root',
        'db_password' => '',
        'db_dbname' => 'skynet',
        'db_encoding' => 'utf-8',
        'db_port' => 3306,

        /* db_file -> string:[filename] <- default: ''
        SQLite database filename, leave empty to let Skynet specify names itself (recommended)  */
        'db_file' => '',
        /* db_file -> string:[path/] <- default: ''
        SQLite database path, if empty db will be created in Skynet directory  */
        'db_file_dir' => '',

        /*
          ==================================
          I) Debug options
          ==================================
        */
        /* console_debug -> bool:[true|false] <- default: true
         If TRUE, console command debugger will be displayed in Control Panel when parsing input */
        'console_debug' => true,

        /* debug_exceptions -> bool:[true|false] <- default: false
        If TRUE, Control Panel will show more info like line, file and trace on errors in Control Panel */
        'debug_exceptions' => false,

        /* debug_internal -> bool:[true|false] <- default: true
         If TRUE, internal params will be show in Control Panel */
        'debug_internal' => true,

        /* debug_echo-> bool:[true|false] <- default: true
         If TRUE, internal @echo params will be show in Control Panel */
        'debug_echo' => true,

        /* debug_key-> bool:[true|false] <- default: true
        If TRUE, KEY ID will be displayed in Control Panel */
        'debug_key' => true

        /*
         -------- end of config.
        */
    ];


    /**
     * Gets config value
     *
     * @param string $key Config Key
     *
     * @return mixed Config value
     */
    public static function get($key)
    {
        if (array_key_exists($key, self::$config)) {
            return self::$config[$key];
        }
    }

    /**
     * Gets all config values as array
     *
     * @return mixed[]
     */
    public static function getAll()
    {
        return self::$config;
    }

    /**
     * Sets config value
     *
     * @param string $key Config Key
     * @param mixed $value Config Value
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }
}