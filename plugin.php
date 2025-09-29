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

		add_action('admin_menu', [self::class, 'settings_page']);
		add_action('admin_init', [self::class, 'add_settings']);
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), [self::class, 'addSettingsLink'] );

		require_once __DIR__.'/includes/TelegramService.php';
		

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
					<li>GitHub: <a href="https://github.com/aiiddqd/fatal-error-sentinel">https://github.com/aiiddqd/fatal-error-sentinel</a></li>
					<li>Support: <a href="https://github.com/aiiddqd/fatal-error-sentinel/issues">https://github.com/aiiddqd/fatal-error-sentinel/issues</a></li>
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

	public static function setConfig($key, $value){
		$config = get_option('fatal_error_sentinel_config', []);
		$config[$key] = $value;
		update_option('fatal_error_sentinel_config', $config);
	}

	public static function getConfig($key = null, $default = null){
		$config = get_option('fatal_error_sentinel_config', []);
		if ($key === null) {
			return $config;
		}
		return $config[$key] ?? $default;
	}

	public static function getConfigFieldName($key){
		return "fatal_error_sentinel_config[$key]";
	}

}