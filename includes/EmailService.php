<?php

namespace FatalErrorSentinel;

EmailService::init();

class EmailService
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings']);
    }

    public static function send_email_notification($error)
    {

        $email = fatal_error_sentinel()->getConfig('notification_email', get_option('admin_email'));

        $website = get_bloginfo('name').' ('.get_bloginfo('url').')';

        $errorToText = self::format_error_for_email($error);

        wp_mail($email, 'Fatal Error: '.$website, $errorToText);

    }

    private static function format_error_for_email($error)
    {
        if (strpos($error['message'], 'Stack trace:') !== false) {
            $parts = explode('Stack trace:', $error['message'], 2);
            if (isset($parts[0])) {
                $message = trim($parts[0]);
            }
            if (isset($parts[1])) {
                $stackTrace = trim($parts[1]);
            }
        } else {
            $message = $error['message'];
            $stackTrace = null;
        }

        $website = get_bloginfo('url').' ('.get_bloginfo('name').')';

        $output = "\n";
        $output .= "Fatal Error Detected: $website\n" . "<br/>";
        $output .= PHP_EOL . "<hr/>";
        $output .= "<pre>";
        $output .= "Message: ".$message;
        $output .= PHP_EOL;
        if ($stackTrace) {
            $output .= "Stack Trace:\n$stackTrace\n";
        }
        $output .= "File: ".(isset($error['file']) ? $error['file'] : 'Unknown')."\n";
        $output .= "Line: ".(isset($error['line']) ? $error['line'] : 'Unknown')."\n";
        $output .= "Type: ".(isset($error['type']) ? $error['type'] : 'Unknown')."\n";
        if ($current_user_id = get_current_user_id()) {
            $output .= "User ID: ".$current_user_id."\n";
        }
        $output .= "Timestamp: ".date('Y-m-d H:i:s')."\n";
        $output .= "</pre>";

        return $output;
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
                $checked = fatal_error_sentinel()->getConfig('email_enabled', false) ? 'checked' : '';
                printf(
                    '<input type="checkbox" name="%s" value="1" %s>',
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('email_enabled')),
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
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('notification_email')),
                    esc_attr(fatal_error_sentinel()->getConfig('notification_email', get_option('admin_email')))
                );
                echo '<p class="description">Enter the email address where fatal error notifications will be sent. Defaults to the site admin email if left blank.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_email_settings'
        );
    }
}
