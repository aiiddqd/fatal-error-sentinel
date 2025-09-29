<?php

namespace FatalErrorSentinel;

TelegramService::init();

class TelegramService
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings']);
    }

    public static function add_settings()
    {

        add_settings_section(
            'fatal_error_sentinel_telegram_settings',
            'Telegram',
            function () {
                echo '<p>Configure the Telegram settings for Fatal Error Sentinel.</p>';
            },
            'fatal-error-sentinel'
        );

        add_settings_field(
            'telegram_enabled',
            'Enable Telegram Notifications',
            function () {
                $checked = Plugin::getConfig('telegram_enabled', false) ? 'checked' : '';
                printf(
                    '<input type="checkbox" name="%s" value="1" %s>',
                    esc_attr(Plugin::getConfigFieldName('telegram_enabled')),
                    $checked
                );
                echo '<p class="description">Check to enable Telegram notifications for fatal errors.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_telegram_settings'
        );

        add_settings_field(
            'telegram_bot_token',
            'Telegram Bot Token',
            function () {
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text">',
                    esc_attr(Plugin::getConfigFieldName('telegram_bot_token')),
                    esc_attr(Plugin::getConfig('telegram_bot_token', ''))
                );
                echo '<p class="description">Enter the Telegram Bot Token used to send notifications.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_telegram_settings'
        );

        add_settings_field(
            'telegram_chat_id',
            'Telegram Chat ID',
            function () {
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text">',
                    esc_attr(Plugin::getConfigFieldName('telegram_chat_id')),
                    esc_attr(Plugin::getConfig('telegram_chat_id', ''))
                );
                echo '<p class="description">Enter the Telegram Chat ID where notifications will be sent.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_telegram_settings'
        );
    }
}