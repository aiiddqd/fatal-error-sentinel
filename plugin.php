<?php
/**
 * Plugin Name: Fatal Error Sentinel
 * Description: Notifications about fatal and critical errors in Email, BetterStack Logs or Telegram
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Plugin URI: https://github.com/aiiddqd/fatal-error-sentinel
 * Author: aiiddqd
 * Author URI: https://github.com/aiiddqd
 * Domain Path: /languages
 * Text Domain: fatal-error-sentinel
 * Version: 0.4.251218
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// glob from includes
foreach (glob(__DIR__.'/includes/*.php') as $file) {
    require_once $file;
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), [fatal_error_sentinel(), 'addSettingsLink']);

add_action('wp_mail_failed', function ($wp_error) {
    error_log('Mail error: '.$wp_error->get_error_message());
}, 10, 1);


/**
 * Get singleton instance
 *
 * @return \FatalErrorSentinel\Plugin
 */
function fatal_error_sentinel()
{
    return \FatalErrorSentinel\Plugin::getInstance();
}

fatal_error_sentinel();
