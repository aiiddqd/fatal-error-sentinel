<?php

namespace FatalErrorSentinel;

BetterStackService::init();

class BetterStackService
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings']);
    }

    public static function sendLog($data)
    {
        $json = json_encode($data);
        $host = Plugin::getConfig('betterstack_url', '');
        //check $host has https or domain
        if (empty($host) || (! str_starts_with($host, 'https://') && ! str_starts_with($host, 'http://'))) {
            $host = 'https://' . $host;
        }

        $result = wp_remote_post($host, [
            'headers' => [
                'Authorization' => 'Bearer '.Plugin::getConfig('betterstack_token'),
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
                <p>Add source here <a href="https://telemetry.betterstack.com/" target="_blank">https://telemetry.betterstack.com/</a></p>
                <?php
            },
            'fatal-error-sentinel'
        );

        add_settings_field(
            'betterstack_enabled',
            'Enable BetterStack Logs Integration',
            function () {
                $checked = Plugin::getConfig('betterstack_enabled', false) ? 'checked' : '';
                printf(
                    '<input type="checkbox" name="%s" value="1" %s>',
                    esc_attr(Plugin::getConfigFieldName('betterstack_enabled')),
                    $checked
                );
                echo '<p class="description">Check to enable BetterStack Logs integration for fatal errors.</p>';
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
                    esc_attr(Plugin::getConfigFieldName('betterstack_token')),
                    esc_attr(Plugin::getConfig('betterstack_token', ''))
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
                    esc_attr(Plugin::getConfigFieldName('betterstack_url')),
                    esc_attr(Plugin::getConfig('betterstack_url', ''))
                );
                echo '<p class="description">Enter the BetterStack Logs URL';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_betterstack_settings'
        );
    }
}
