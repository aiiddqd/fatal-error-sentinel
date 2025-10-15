<?php

namespace FatalErrorSentinel;

EmailService::init();

class EmailService
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings']);
    }

    public static function add_settings()
    {

        add_settings_section(
            'fatal_error_sentinel_email_settings',
            'Email Notifications',
            function () {
                echo '<p>Configure the email settings for Fatal Error Sentinel.</p>';
            },
            'fatal-error-sentinel'
        );

        add_settings_field(
            'email_enabled',
            'Enable Email Notifications',
            function () {
                $checked = Plugin::getConfig('email_enabled', false) ? 'checked' : '';
                printf(
                    '<input type="checkbox" name="%s" value="1" %s>',
                    esc_attr(Plugin::getConfigFieldName('email_enabled')),
                    $checked
                );
                echo '<p class="description">Check to enable email notifications for fatal errors.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_email_settings'
        );

        add_settings_field(
            'notification_email',
            'Notification Email Address',
            function () {
                printf(
                    '<input type="email" name="%s" value="%s" class="regular-text">',
                    esc_attr(Plugin::getConfigFieldName('notification_email')),
                    esc_attr(Plugin::getConfig('notification_email', get_option('admin_email')))
                );
                echo '<p class="description">Enter the email address where fatal error notifications will be sent. Defaults to the site admin email if left blank.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_email_settings'
        );
    }
}
