<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Recursively delete the plugin's email log attachment copies folder
 * (wp-content/uploads/yaysmtp/email-log-attachments) for the current site.
 */
function yaysmtp_uninstall_remove_email_log_attachments() {
	$uploads = wp_upload_dir( null, false );
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return;
	}

	$dir = trailingslashit( $uploads['basedir'] ) . 'yaysmtp/email-log-attachments';
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			@rmdir( $file->getRealPath() ); // phpcs:ignore
		} else {
			@unlink( $file->getRealPath() ); // phpcs:ignore
		}
	}
	@rmdir( $dir ); // phpcs:ignore
}

if ( current_user_can( 'manage_options' ) ) {
	$uninstallFlag   = 'no';
	$yaysmtpSettings = get_option( 'yaysmtp_settings' );
	if ( ! empty( $yaysmtpSettings ) && is_array( $yaysmtpSettings ) ) {
		if ( ! empty( $yaysmtpSettings['uninstall_flag'] ) ) {
			$uninstallFlag = $yaysmtpSettings['uninstall_flag'];
		}
	}

	if ( 'yes' === $uninstallFlag ) {
		global $wpdb;
		$allowMultisite = 'no';
		if ( is_multisite() ) {
			$yaysmtpMainSettings = get_blog_option( get_main_site_id(), 'yaysmtp_settings', array() );
			if ( ! empty( $yaysmtpMainSettings ) && ! empty( $yaysmtpMainSettings['allowMultisite'] ) ) {
				$allowMultisite = $yaysmtpMainSettings['allowMultisite'];
			}
		}

		if ( is_multisite() && ( 'yes' === $allowMultisite ) ) {
			$siteList = get_sites();
			foreach ( (array) $siteList as $site ) {
				switch_to_blog( $site->blog_id );

				// Delete option data
				delete_option( 'yaysmtp_settings' );
				delete_option( 'yaysmtp_settings_bk' );
				delete_option( 'yaysmtp_email_log_settings' );
				delete_option( 'yaysmtp_email_log_settings_bk' );
				delete_option( 'yaysmtp_debug' );
				delete_option( 'yaysmtp_debug_fallback' );
				delete_option( 'yay_smtp_version' );

				// Delete table
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaysmtp_email_logs;" );
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaysmtp_event_email_opened;" );
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaysmtp_event_email_clicked_link;" );

				yaysmtp_uninstall_remove_email_log_attachments();

				restore_current_blog();
			}
		} else {
			// Delete option data
			delete_option( 'yaysmtp_settings' );
			delete_option( 'yaysmtp_settings_bk' );
			delete_option( 'yaysmtp_email_log_settings' );
			delete_option( 'yaysmtp_email_log_settings_bk' );
			delete_option( 'yaysmtp_debug' );
			delete_option( 'yaysmtp_debug_fallback' );
			delete_option( 'yay_smtp_version' );

			// Delete table
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaysmtp_email_logs;" );
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaysmtp_event_email_opened;" );
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaysmtp_event_email_clicked_link;" );

			yaysmtp_uninstall_remove_email_log_attachments();
		}
	}
}
