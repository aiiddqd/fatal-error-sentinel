<?php

namespace FatalErrorSentinel;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class Plugin
{
    /**
     * Singleton instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize singleton.');
    }

    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize plugin
     *
     * @return void
     */
    public function init()
    {

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
            asdfsdf2();

        });


        add_action('admin_menu', [$this, 'settings_page']);
        add_action('admin_init', [$this, 'add_settings'], 5);
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$this, 'addSettingsLink']);

        $this->catchErrors();
    }

    /**
     * Send error notification
     *
     * @param array $error
     * @return void
     */
    public function send_error($error)
    {
        $message = explode('Stack trace:', $error['message']);

        $data = [
            'message' => trim($message[0]),
            'nested' => [],
        ];

        if (isset($message[1])) {
            $data['nested']['stack_trace'] = explode("\n", trim($message[1]));
        } else {
            $data['nested']['debug_backtrace'] = $error;
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

        if ($this->getConfig('email_enabled', false)) {
            EmailService::send_email_notification($error);
        }

        if ($this->getConfig('telegram_enabled', false)) {
            TelegramService::send_error($error);
        }

        if ($this->getConfig('betterstack_enabled', false)) {
            BetterStackService::sendLog($data);
        }

        do_action('fatal_error_sentinel_send_error', $data);
    }

    /**
     * Catch fatal errors
     *
     * @return void
     */
    public function catchErrors()
    {


        if (! $this->isEnabled()) {
            return;
        }

        add_action('shutdown', function () {
            $error = error_get_last();

            if (empty($error['type'])) {
                return;
            }

            if ($error['type'] != E_ERROR) {
                return;
            }


            $this->send_error($error);
        }, 11);

        // add_filter('wp_php_error_message', function ($message, $error) {
        //     $this->send_error($error);

        //     return $message;
        // }, 11, 2);
    }

    /**
     * Check if plugin is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->getConfig('telegram_enabled', false)) {
            return true;
        }

        if ($this->getConfig('email_enabled', false)) {
            return true;
        }

        if ($this->getConfig('betterstack_enabled', false)) {
            return true;
        }

        return false;
    }

    /**
     * Add settings link to plugin actions
     *
     * @param array $links
     * @return array
     */
    public function addSettingsLink($links)
    {
        $settings_link = '<a href="options-general.php?page=fatal-error-sentinel">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }


    /**
     * Register plugin settings
     *
     * @return void
     */
    public function add_settings()
    {
        register_setting('fatal_error_sentinel_options', 'fatal_error_sentinel_config');
        add_settings_section(
            'fatal_error_sentinel_settings_general',
            'General Settings',
            function () {
                ?>
            <p>Configure the general settings for Fatal Error Sentinel.</p>

            <ul>
                <li>GitHub: <a
                        href="https://github.com/aiiddqd/fatal-error-sentinel">https://github.com/aiiddqd/fatal-error-sentinel</a>
                </li>
                <li>Support: <a
                        href="https://github.com/aiiddqd/fatal-error-sentinel/issues">https://github.com/aiiddqd/fatal-error-sentinel/issues</a>
                </li>
            </ul>
            <p>Test link: <a
                    href="<?= add_query_arg('test_FatalErrorSentinel', '1', admin_url('options-general.php?page=fatal-error-sentinel')) ?>"
                    target="_blank">Check</a></p>

            <?php
            },
            'fatal-error-sentinel'
        );

    }


    /**
     * Add settings page
     *
     * @return void
     */
    public function settings_page()
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

    /**
     * Set configuration option
     *
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function setConfig($key, $value)
    {
        $config = get_option('fatal_error_sentinel_config', []);
        $config[$key] = $value;
        update_option('fatal_error_sentinel_config', $config);
    }

    /**
     * Get configuration option
     *
     * @param string|null $key
     * @param mixed       $default
     * @return mixed
     */
    public function getConfig($key = null, $default = null)
    {
        $config = get_option('fatal_error_sentinel_config', []);
        if ($key === null) {
            return $config;
        }
        return $config[$key] ?? $default;
    }

    /**
     * Get configuration field name
     *
     * @param string $key
     * @return string
     */
    public function getConfigFieldName($key)
    {
        return "fatal_error_sentinel_config[$key]";
    }
}