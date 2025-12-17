<?php

namespace FatalErrorSentinel;

TelegramService::init();

class TelegramService
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings'], 20);

        //add rest api endpoint for telegram webhook /wp-json/fatal-error-sentinel/v1/telegram-webhook'
        add_action('rest_api_init', function () {
            register_rest_route('fatal-error-sentinel/v1', '/telegram-webhook', [
                'methods' => 'POST',
                'callback' => [self::class, 'handle_telegram_webhook'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public static function send_error($error)
    {

        $chat_id = fatal_error_sentinel()->getConfig('telegram_chat_id', '');
        if (empty($chat_id)) {
            return;
        }
        
        $website = get_bloginfo('name').' ('.get_bloginfo('url').')';

        $errorToText = self::format_error_to_text($error);

        self::send_telegram_message($chat_id, $errorToText);
    }

    public static function format_error_to_text($error)
    {
        if (strpos($error['message'], 'Stack trace:') !== false) {
            $parts = explode('Stack trace:', $error['message'], 2);
            if (isset($parts[0])) {
                $message = trim(esc_html($parts[0]));
            }
            if (isset($parts[1])) {
                $stackTrace = trim(esc_html($parts[1]));
            }
        } else {
            $message = esc_html($error['message']);
            $stackTrace = null;
        }

        // $message - can't parse entities: Character '(' is reserved and must be escaped with the preceding '\\'"}

        $website = get_bloginfo('url').' ('.get_bloginfo('name').')';
        if ($_SERVER['REQUEST_URI']) {
            $request = $_SERVER['REQUEST_URI'];
        } else {
            $request = 'unknown';
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = urlencode($_SERVER['HTTP_REFERER']);
        } else {
            $referrer = 'unknown';
        }

        $output = "\n";
        $output .= "Fatal Error Detected: $website\n";
        // $output .= PHP_EOL."<hr/>";
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
        $output .= 'Request: '.$request."\n";
        $output .= 'Referrer: '.$referrer."\n";

        $output .= "Timestamp: ".date('Y-m-d H:i:s')."\n";
        $output .= "</pre>";

        return $output;
    }


    //handle_telegram_webhook
    public static function handle_telegram_webhook($request)
    {
        $data = $request->get_json_params();

        // Process incoming messages or commands here
        if (isset($data['message'])) {
            $chat_id = $data['message']['chat']['id'];
            $text = $data['message']['text'];

            // Example: Respond to /start command
            if ($text === '/start') {
                $message = 'Welcome to Fatal Error Sentinel Bot!';
                $message .= "\nYour Chat ID is: ".$chat_id;
                $message .= "\nGo to settings: ".admin_url('options-general.php?page=fatal-error-sentinel');
                self::send_telegram_message($chat_id, $message);
            } else {
                $message = 'You said: '.$text;
                $message .= "\nYour Chat ID is: ".$chat_id;
                $message .= "\nGo to settings: ".admin_url('options-general.php?page=fatal-error-sentinel');
                self::send_telegram_message($chat_id, $message);
            }
        }

        return new \WP_REST_Response('OK', 200);
    }

    //send_telegram_message
    public static function send_telegram_message($chat_id, $message)
    {
        $bot_token = fatal_error_sentinel()->getConfig('telegram_bot_token', '');

        if (empty($bot_token) || empty($chat_id)) {
            return;
        }

        $api_url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

        $response = wp_remote_post($api_url, [
            'body' => [
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('Failed to send Telegram message: '.$response->get_error_message());
            return;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        if (isset($result['ok']) && $result['ok']) {
            error_log('Telegram message sent successfully.');
        } else {
            error_log('Failed to send Telegram message: '.$response_body);
        }
    }


    static public function check_and_set_telegram_webhook()
    {
        $bot_token = fatal_error_sentinel()->getConfig('telegram_bot_token', '');
        // $chat_id = fatal_error_sentinel()->getConfig('telegram_chat_id', '');

        if (empty($bot_token)) {
            return;
        }

        $webhook_url = rest_url('fatal-error-sentinel/v1/telegram-webhook');

        //check current webhook
        $api_url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";

        $response = wp_remote_post($api_url, []);

        if (is_wp_error($response)) {
            error_log('Failed to get Telegram webhook info: '.$response->get_error_message());
            return;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        if (isset($result['ok']) && $result['ok']) {
            $current_url = $result['result']['url'] ?? '';

            // Skip setting webhook if it's already correct
            if ($current_url === $webhook_url) {
                // error_log('Telegram webhook already set correctly.');
                set_transient('fatal_error_sentinel_telegram_webhook_set', true, HOUR_IN_SECONDS);
                return;
            }
        }

        $api_url = "https://api.telegram.org/bot{$bot_token}/setWebhook";

        $response = wp_remote_post($api_url, [
            'body' => [
                'url' => $webhook_url,
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('Failed to set Telegram webhook: '.$response->get_error_message());
            return;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        if (isset($result['ok']) && $result['ok']) {
            error_log('Telegram webhook set successfully.');
        } else {
            error_log('Failed to set Telegram webhook: '.$response_body);
        }
    }

    public static function add_settings()
    {
        add_settings_section(
            'fatal_error_sentinel_telegram_settings',
            'Telegram Notifications',
            function () {
                echo '<p>Configure the Telegram settings for Fatal Error Sentinel.</p>';
            },
            'fatal-error-sentinel'
        );

        add_settings_field(
            'telegram_bot_token',
            'Telegram Bot Token',
            function () {
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text">',
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('telegram_bot_token')),
                    esc_attr(fatal_error_sentinel()->getConfig('telegram_bot_token', ''))
                );
                echo '<p class="description">Get the Telegram Bot Token from <a href="https://t.me/botfather" target="_blank" rel="noopener noreferrer">https://t.me/botfather</a>.</p>';

                if (get_transient('fatal_error_sentinel_telegram_webhook_set')) {
                    echo '<p style="color:green;">Telegram webhook is set correctly.</p>';
                } else {
                    if ($token = fatal_error_sentinel()->getConfig('telegram_bot_token', '')) {
                        echo '<p style="color:red;">Telegram webhook is not set. It will be set automatically when you save the settings.</p>';
                        self::check_and_set_telegram_webhook();
                    }
                }
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
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('telegram_chat_id')),
                    esc_attr(fatal_error_sentinel()->getConfig('telegram_chat_id', ''))
                );
                echo '<p class="description">Enter the Telegram Chat ID where notifications will be sent.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_telegram_settings'
        );

        add_settings_field(
            'telegram_enabled',
            'Enable Telegram Notifications',
            function () {
                $checked = fatal_error_sentinel()->getConfig('telegram_enabled', false) ? 'checked' : '';
                printf(
                    '<input type="checkbox" name="%s" value="1" %s>',
                    esc_attr(fatal_error_sentinel()->getConfigFieldName('telegram_enabled')),
                    $checked
                );
                echo '<p class="description">Check to enable Telegram notifications for fatal errors.</p>';
            },
            'fatal-error-sentinel',
            'fatal_error_sentinel_telegram_settings'
        );
    }

}
