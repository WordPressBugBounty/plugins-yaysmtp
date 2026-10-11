<?php
/**
 * Plugin Name: YaySMTP
 * Plugin URI: https://yaycommerce.com/yaysmtp
 * Description: This plugin helps you send emails from your WordPress website via your preferred SMTP server.
 * Version: 2.7.8
 * Author: YayCommerce
 * Author URI: https://yaycommerce.com
 * Text Domain: yaysmtp
 * Domain Path: /i18n/languages/
 */

namespace YaySMTP;

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'YaySMTP\\init' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	require_once ABSPATH . WPINC . '/pluggable.php';
	deactivate_plugins( plugin_basename( __FILE__ ) );
	require_once plugin_dir_path( __FILE__ ) . 'includes/UpdateVersion.php';
	return;
}

if ( ! defined( 'YAY_SMTP_PREFIX' ) ) {
	define( 'YAY_SMTP_PREFIX', 'yay-smtp' );
}
if ( ! defined( 'YAY_SMTP_VERSION' ) ) {
	define( 'YAY_SMTP_VERSION', '2.7.8' );
}

if ( ! defined( 'YAY_SMTP_DOMAIN' ) ) {
	define( 'YAY_SMTP_DOMAIN', 'yay-smtp' );
}

if ( ! defined( 'YAY_SMTP_PLUGIN_URL' ) ) {
	define( 'YAY_SMTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'YAY_SMTP_PLUGIN_PATH' ) ) {
	define( 'YAY_SMTP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YAY_SMTP_PLUGIN_BASENAME' ) ) {
	define( 'YAY_SMTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YAY_SMTP_SITE_URL' ) ) {
	define( 'YAY_SMTP_SITE_URL', site_url() );
}

if ( ! defined( 'YAY_SMTP_PLUGIN_NAME' ) ) {
	define( 'YAY_SMTP_PLUGIN_NAME', 'yay-smtp' );
}


spl_autoload_register(
	function ( $class ) {
		$prefix   = __NAMESPACE__; // project-specific namespace prefix
		$base_dir = __DIR__ . '/includes'; // base directory for the namespace prefix

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) { // does the class use the namespace prefix?
			return; // no, move to the next registered autoloader
		}

		$relative_class_name = substr( $class, $len );

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $base_dir . str_replace( '\\', '/', $relative_class_name ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/YaysmtpPluginAdapter.php';  // unique per plugin

add_action( 'plugins_loaded', function() {
    \YaySMTPScoped\YayCommerce\AdminShell\AdminShell::boot();
    \YaySMTPScoped\YayCommerce\AdminShell\AdminShell::register_plugin(
        new \YaysmtpPluginAdapter()
    );
}, 5 );

require_once __DIR__ . '/vendor_google_microsoft/autoload.php';
require_once __DIR__ . '/vendor_amazon/autoload.php';

if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
	if ( ! class_exists( '\PHPMailer', false ) ) {
		require_once ABSPATH . 'wp-includes/class-phpmailer.php';
	}
} else {
	if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) {
		require_once ABSPATH . 'wp-includes/PHPMailer/PHPMailer.php';
	}
	if ( ! class_exists( '\PHPMailer\PHPMailer\Exception', false ) ) {
		require_once ABSPATH . 'wp-includes/PHPMailer/Exception.php';
	}
	if ( ! class_exists( '\PHPMailer\PHPMailer\SMTP', false ) ) {
		require_once ABSPATH . 'wp-includes/PHPMailer/SMTP.php';
	}
}

if ( ! function_exists( 'YaySMTP\\init' ) ) {
	function init() {
		Dashboard::getInstance();
		Schedule::getInstance();
		Plugin::getInstance();
		I18n::getInstance();
		ImportSettingsOtherPlugins::getInstance();
		TrackingEvents\TrackingEventApi::getInstance();
		CLI\CLI::getInstance();
	}
}

add_action( 'plugins_loaded', 'YaySMTP\\init' );

register_activation_hook( __FILE__, array( 'YaySMTP\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'YaySMTP\\Plugin', 'deactivate' ) );
