<?php
namespace YaySMTP\CLI;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils as WP_CLI_Utils;

use YaySMTP\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List email logs sent through YaySMTP.
 *
 * ## EXAMPLES
 *
 *     # List all email logs
 *     $ wp yaysmtp-email-log list
 *
 *     # List email logs with specific status
 *     $ wp yaysmtp-email-log list --status=success
 *     $ wp yaysmtp-email-log list --status=failed
 * 
 *     # List email logs with date range
 *     $ wp yaysmtp-email-log list --from=2024-01-01 --to=2024-03-20
 *
 *     # List email logs with limit
 *     $ wp yaysmtp-email-log list --limit=10
 *
 *     # List email logs with admin user
 *     $ wp yaysmtp-email-log list --user=admin
 *     
 *     # List email logs with specific site url if you have multiple sites
 *     $ wp yaysmtp-email-log list --site=https://example.com
 */
class EmailLogCommand extends WP_CLI_Command {
	const LIMIT = 50;

	/**
	 * List email logs sent through YaySMTP.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter by status (success/failed)
	 *
	 * [--from=<from>]
	 * : Filter by start date (YYYY-MM-DD)
	 *
	 * [--to=<to>]
	 * : Filter by end date (YYYY-MM-DD)
	 *
	 * [--limit=<limit>]
	 * : Number of records to show (default: 20)
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function list( $args, $assoc_args ) {
		// $this->check_user_permissions();
		
		$arguments = Utils::saniValArray( $assoc_args );

		global $wpdb;

		$table_name = $wpdb->prefix . 'yaysmtp_email_logs';
		
		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			WP_CLI::error( __( 'Email logs table does not exist.', 'yaysmtp' ) );
			return;
		}

		// Build query
		$where_clauses = [];
		$query_params = [];

		// Status filter
		$status = WP_CLI_Utils\get_flag_value( $arguments, 'status', '' );
		if ( ! empty( $status ) ) {
			$status = strtolower( $status );
			if ( $status === 'success' ) {
				$where_clauses[] = 'status = %d';
				$query_params[] = 1;
			} elseif ( $status === 'failed' ) {
				$where_clauses[] = 'status = %d';
				$query_params[] = 0;
			}
		}

		// Date range filter
		$from = WP_CLI_Utils\get_flag_value( $arguments, 'from', '' );
		if ( ! empty( $from ) ) {
			$where_clauses[] = 'DATE(date_time) >= %s';
			$query_params[] = $from;
		}
		$to = WP_CLI_Utils\get_flag_value( $arguments, 'to', '' );
		if ( ! empty( $to ) ) {
			$where_clauses[] = 'DATE(date_time) <= %s';
			$query_params[] = $to;
		}

		// Build WHERE clause
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Limit
		$limit = absint( WP_CLI_Utils\get_flag_value( $arguments, 'limit', self::LIMIT ) );
		$limit_sql = 'LIMIT %d';
		$query_params[] = $limit;

		// Prepare and execute query
		$query = "SELECT * FROM $table_name $where_sql ORDER BY date_time DESC $limit_sql";
		$query = $wpdb->prepare( $query, $query_params );
		$results = $wpdb->get_results( $query );

		if ( empty( $results ) ) {
			WP_CLI::log( __( 'No email logs found.', 'yaysmtp' ) );
			return;
		}

		// Format results
		$date_time_format = get_option( 'date_format' ) . " \\a\\t " . get_option( 'time_format' );
		$formatted_results = [];
		foreach ( $results as $result ) {
			$formatted_results[] = [
				'ID'          => $result->id,
				'Date'        => get_date_from_gmt( $result->date_time, $date_time_format ),
				'From'        => $result->email_from,
				'To'          => implode( ', ', maybe_unserialize( $result->email_to ) ),
				'Subject'     => $result->subject,
				'Status'      => $result->status ? 'Success' : 'Failed',
				'Mailer'      => $result->mailer,
			];
		}

		// Output results
		$format = WP_CLI_Utils\get_flag_value( $arguments, 'format', 'table' );
		// Show the results in the format table
		$this->print_results( $formatted_results, $format );
	}

	private function check_user_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			WP_CLI::error( __( 'You do not have sufficient permissions to access this command.', 'yaysmtp' ) );
			return;
		}
	}

	private function print_results( $results, $format ) {
		$fields = [
			'ID',
			'Date',
			'From',
			'To',
			'Subject',
			'Status',
			'Mailer'
		];

		WP_CLI_Utils\format_items( $format, $results, $fields );
	}
} 