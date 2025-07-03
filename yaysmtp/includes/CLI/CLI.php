<?php
namespace YaySMTP\CLI;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CLI {
    protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->doHooks();
		}

		return self::$instance;
	}

	private function __construct() {
	}

	private function doHooks() {
		// if ( ! current_user_can( 'manage_options' ) ) {
		// 	return;
		// }

		add_action( 'init', array( $this, 'register_commands' ) );
	}

	public static function register_commands() {
        $allow_use_cli = apply_filters( 'yaysmtp_allow_use_cli', false );
        if ( ! $allow_use_cli ) {
            return;
        }

		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
			// Add EmailLogCommand to WP CLI
			WP_CLI::add_command( 'yaysmtp-email-log', 'YaySMTP\CLI\EmailLogCommand' );
		}
	}
}
