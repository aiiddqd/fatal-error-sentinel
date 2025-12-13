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
 * Version: 0.3.251016
 */



if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// glob from includes
foreach (glob(__DIR__ . '/includes/*.php') as $file) {
    require_once $file;
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), [fatal_error_sentinel(), 'addSettingsLink']);

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