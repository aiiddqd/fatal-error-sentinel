<?php
/**
 * Plugin Name: Fatal Error Sentinel
 * Description: Notifications about fatal and critical errors in BetterStack Logs, Email or Telegram
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Plugin URI: https://github.com/aiiddqd/fatal-error-sentinel
 * Author: aiiddqd
 * Author URI: https://github.com/aiiddqd
 * Domain Path: /languages
 * Text Domain: fatal-error-sentinel
 * Version: 0.3.250928
 */

namespace FatalErrorSentinel;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

Plugin::init();

class Plugin
{
    public static function init()
    {

        add_action('admin_init', function () {
            if (! isset($_GET['dddd']))
                return;
            va1r_dump(1);
            exit;
        });


        /**
         * simple test for check BetterStack
         *
         * 1. just run {{siteUrl}}/?test_BetterStackLogsIntegration
         * 2. check logs https://telemetry.betterstack.com/
         */
        add_action('init', function () {

            if (! isset($_GET['test_FatalErrorSentinel'])) {
                return;
            }

            // $r = BetterStackService::sendLog([
            //     'message' => 'Test Fatal Error Sentinel 2',
            //     'nested' => [
            //         'test_field' => 'test_value',
            //     ],
            // ]);
            // var_dump($r);
            // asdfsdf();
            // exit;
        });


        add_action('admin_menu', [self::class, 'settings_page']);
        add_action('admin_init', [self::class, 'add_settings']);
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), [self::class, 'addSettingsLink']);

        require_once __DIR__.'/includes/TelegramService.php';
        require_once __DIR__.'/includes/EmailService.php';
        require_once __DIR__.'/includes/BetterStackService.php';

        self::catchErrors();

    }


    public static function send_error($error)
    {
        $message = explode('Stack trace:', $error['message']);

        $data = [
            'message' => trim($message[0]),
            'nested' => [],
        ];

        if (isset($message[1])) {
            $data['nested']['stack_trace'] = explode("\n", trim($message[1]));
        }

        if ($user_id = get_current_user_id()) {
            $data['nested']['user_id'] = $user_id;
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $data['nested']['request'] = $_SERVER['REQUEST_URI'];
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $data['nested']['referer'] = $_SERVER['HTTP_REFERER'];
        } else {
            $data['nested']['referer'] = 'unknown';
        }

        if (isset($error['type'])) {
            $data['nested']['type'] = $error['type'];
        }
        // error_log('test: '.print_r($data, true));
        // var_dump($data); exit;
        // require_once __DIR__.'/includes/BetterStackService.php';


        BetterStackService::sendLog($data);

        do_action('fatal_error_sentinel_send_error', $data);
    }


    public static function catchErrors()
    {
        if (! self::isEnabled()) {
            return;
        }

        add_action('shutdown', function () {

            $error = error_get_last();

            if (is_null($error)) {
                return;
            }

            if ($error['type'] != E_ERROR) {
                return;
            }

            self::send_error($error);
        }, 1);

        add_filter('wp_php_error_message', function ($message, $error) {

            self::send_error($error);

            return $message;
        }, 11, 2);
    }

    public static function isEnabled()
    {
        if (self::getConfig('telegram_enabled', false)) {
            return true;
        }

        if (self::getConfig('email_enabled', false)) {
            return true;
        }

        if (self::getConfig('betterstack_enabled', false)) {
            return true;
        }

        return false;
    }



    public static function addSettingsLink($links)
    {
        $settings_link = '<a href="options-general.php?page=fatal-error-sentinel">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public static function add_settings()
    {
        register_setting('fatal_error_sentinel_options', 'fatal_error_sentinel_config');
        add_settings_section(
            'fatal_error_sentinel_settings_general',
            'General Settings',
            function () {
                ?>
            <ul>
                <li>GitHub: <a
                        href="https://github.com/aiiddqd/fatal-error-sentinel">https://github.com/aiiddqd/fatal-error-sentinel</a>
                </li>
                <li>Support: <a
                        href="https://github.com/aiiddqd/fatal-error-sentinel/issues">https://github.com/aiiddqd/fatal-error-sentinel/issues</a>
                </li>
                <li>Author: <a href="https://github.com/aiiddqd">https://github.com/aiiddqd</a></li>
            </ul>
            <p>Configure the general settings for Fatal Error Sentinel.</p>

            <?php
            },
            'fatal-error-sentinel'
        );

    }



    //add settings page
    public static function settings_page()
    {
        add_options_page(
            'Fatal Error Sentinel - Settings',
            'Fatal Error Sentinel',
            'manage_options',
            'fatal-error-sentinel',
            function () { ?>
            <div class="wrap">
                <h1>Fatal Error Sentinel</h1>
                <form method="post" action="options.php">
                    <?php
                        settings_fields('fatal_error_sentinel_options');
                        do_settings_sections('fatal-error-sentinel');
                        submit_button();
                        ?>
                </form>
            </div>
            <?php
            }
        );
    }

    public static function setConfig($key, $value)
    {
        $config = get_option('fatal_error_sentinel_config', []);
        $config[$key] = $value;
        update_option('fatal_error_sentinel_config', $config);
    }

    public static function getConfig($key = null, $default = null)
    {
        $config = get_option('fatal_error_sentinel_config', []);
        if ($key === null) {
            return $config;
        }
        return $config[$key] ?? $default;
    }

    public static function getConfigFieldName($key)
    {
        return "fatal_error_sentinel_config[$key]";
    }

}
