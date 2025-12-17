<?php

namespace FatalErrorSentinel;

BetterStackService::init();

class BetterStackService
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings'], 30);
    }

    /**
     * Send a fatal error to the BetterStack logging service.
     *
     * @param array $error Error data, including message and optional type.
     *
     * @return void
     */
    public static function send_error($error)
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
            $data['nested']['request'] = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $data['nested']['referer'] = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
        } else {
            $data['nested']['referer'] = 'unknown';
        }

        if (isset($error['type'])) {
            $data['nested']['type'] = $error['type'];
        }

        self::sendLog($data);
    }

    public static function send_wp_error($wp_error)
    {
        $data = [
            'message' => sprintf("Ошибка отправки почты: %s", $wp_error->get_error_message()),
        ];

        self::sendLog($data);
    }


    public static function sendLog($data)
    {
        $json = json_encode($data);
        $host = fatal_error_sentinel()->getConfig('betterstack_url', '');
        //check $host has https or domain
        if (empty($host) || (! str_starts_with($host, 'https://') && ! str_starts_with($host, 'http://'))) {
            $host = 'https://'.$host;
        }

        $result = wp_remote_post($host, [
            'headers' => [
                'Authorization' => 'Bearer '.fatal_error_sentinel()->getConfig('betterstack_token'),
                'Content-Type' => 'application/json'
            ],
            'body' => $json,
        ]);

        return $result;
    }

    public static function add_settings()
    {

        add_settings_section(
            'fatal_error_sentinel_betterstack_settings',
            'BetterStack Logs Integration',
            function () {
                ?>
            <p>Configure the BetterStack Logs settings for Fatal Error Sentinel.</p>
            <p>Add source here <a href="https://telemetry.betterstack.com/" target="_blank">https://telemetry.betterstack.com/</a>
            </p>
            <?php
            },
            'fatal-error-sentinel'
        );

        add_settings_field(
            'betterstack_enabled',
            'Enable BetterStack Logs Integration',
            function () {
                $checked = fatal_error_sentinel()->getConfig('betterstack_enabled', false) ? 'checked' : '';
                printf(
                    '<input type="checkbox" name="%s" value="1" %s>',
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('betterstack_enabled')),
                    $checked
                );
                echo '<p class="description">Check to enable BetterStack Logs after checking the checklist below.</p>';
                ob_start();
                ?>
            <div>
                <br>
                <hr>
                <strong>Checklist:</strong>
                <ul>
                    <li>- Create a BetterStack Logs account</li>
                    <li>- Create a Logs Source (HTTP)</li>
                    <li>- Copy the Source Token and paste it below</li>
                    <li>- Copy the Logs URL and paste it below</li>
                </ul>
            </div>
        <?php
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_betterstack_settings'
        );

        add_settings_field(
            'betterstack_token',
            'BetterStack Logs Source Token',
            function () {
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text">',
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('betterstack_token')),
                    esc_attr(fatal_error_sentinel()->getConfig('betterstack_token', ''))
                );
                echo '<p class="description">Enter the BetterStack Logs Source Token used to send logs. You can find this token in your BetterStack Logs account.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_betterstack_settings'
        );

        //add settings field - url
        add_settings_field(
            'betterstack_url',
            'BetterStack Logs URL',
            function () {
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text">',
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('betterstack_url')),
                    esc_attr(fatal_error_sentinel()->getConfig('betterstack_url', ''))
                );
                echo '<p class="description">Enter the BetterStack Logs URL';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_betterstack_settings'
        );
    }
}
