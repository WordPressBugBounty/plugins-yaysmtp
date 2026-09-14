<?php

namespace YaySMTP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store the attachments of a logged email so it can be resent later with the
 * original files, even after the source (often a temporary file) is gone.
 *
 * Storage is content addressed: every attachment is saved once as
 * `{uploads}/yaysmtp/email-log-attachments/{ab}/{sha256}.{ext}` and shared by
 * every log that carries the same bytes. The log row keeps only lightweight
 * metadata (name, hash, size, ...). A physical file is removed once no log
 * references its hash any more.
 */
class EmailLogAttachments {

	/**
	 * Uploads sub directory (relative to wp-content/uploads) where copies live.
	 */
	const DIR_NAME = 'yaysmtp/email-log-attachments';

	/**
	 * Default per-file cap. Larger files are referenced by name only (not copied),
	 * so the resend UI can still show what was attached originally.
	 */
	const DEFAULT_MAX_FILE_SIZE = 15728640; // 15 MB.

	/**
	 * Default cap for the sum of copied files of a single email.
	 */
	const DEFAULT_MAX_TOTAL_SIZE = 41943040; // 40 MB.

	/**
	 * Whether saving attachment copies for email logs is enabled.
	 *
	 * @return bool
	 */
	public static function isEnabled() {
		$setting = Utils::getYaySmtpEmailLogSetting();
		$save    = isset( $setting['save_email_log'] ) ? $setting['save_email_log'] : 'yes';

		if ( 'yes' !== $save ) {
			return false;
		}

		// "Basic info" logging deliberately drops heavy data (body, etc.) - keep
		// attachment copies out of it too.
		$inf_type = isset( $setting['email_log_inf_type'] ) ? $setting['email_log_inf_type'] : 'full_inf';
		if ( 'basic_inf' === $inf_type ) {
			return false;
		}

		// Optional dedicated toggle; defaults to enabled when absent.
		$enabled = ! isset( $setting['save_email_log_attachments'] ) || 'no' !== $setting['save_email_log_attachments'];

		return (bool) apply_filters( 'yaysmtp_save_email_log_attachments', $enabled );
	}

	/**
	 * Whether the `attachments` column exists on the email logs table. Result is
	 * cached for the request. Keeps the feature fully inert (no errors) on installs
	 * where the DB migration has not run yet.
	 *
	 * @return bool
	 */
	public static function schemaReady() {
		static $ready = null;

		if ( null !== $ready ) {
			return $ready;
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'yaysmtp_email_logs';
		$column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'attachments' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- schema probe, table name is a fixed prefix.

		$ready = ! empty( $column );

		return $ready;
	}

	/**
	 * Absolute path to the root folder that holds every attachment copy.
	 *
	 * @return string Trailing-slashed path, or '' when uploads are unavailable.
	 */
	public static function getRootDir() {
		$uploads = wp_upload_dir( null, false );
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return '';
		}
		return trailingslashit( trailingslashit( $uploads['basedir'] ) . self::DIR_NAME );
	}

	/**
	 * Absolute path of the shared file for a given content hash.
	 *
	 * @param string $hash Content hash (hex).
	 * @param string $ext  Lower-case extension without the dot.
	 * @return string Path, or '' when the hash is invalid or uploads unavailable.
	 */
	public static function pathForHash( $hash, $ext = '' ) {
		$hash = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $hash ) );
		$root = self::getRootDir();
		if ( strlen( $hash ) < 8 || '' === $root ) {
			return '';
		}
		$ext = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $ext ) );
		return $root . substr( $hash, 0, 2 ) . '/' . $hash . ( '' !== $ext ? '.' . $ext : '' );
	}

	/**
	 * Copy the sending email's attachments into the shared store (skipping any
	 * whose bytes are already there) and return the metadata to persist on the
	 * log row.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer|object $phpmailer PHPMailer instance.
	 * @param int                                   $log_id    Email log id.
	 * @return array<int,array<string,mixed>> Metadata entries; empty when nothing stored.
	 */
	public static function store( $phpmailer, $log_id ) {
		$log_id = absint( $log_id );

		if ( empty( $log_id ) || ! self::isEnabled() || ! self::schemaReady() ) {
			return array();
		}
		if ( ! is_object( $phpmailer ) || ! method_exists( $phpmailer, 'getAttachments' ) ) {
			return array();
		}

		$attachments = $phpmailer->getAttachments();
		if ( empty( $attachments ) || ! is_array( $attachments ) ) {
			return array();
		}

		$root = self::getRootDir();
		if ( '' === $root ) {
			return array();
		}

		$max_file  = (int) apply_filters( 'yaysmtp_email_log_attachment_max_file_size', self::DEFAULT_MAX_FILE_SIZE );
		$max_total = (int) apply_filters( 'yaysmtp_email_log_attachment_max_total_size', self::DEFAULT_MAX_TOTAL_SIZE );

		$meta        = array();
		$total_bytes = 0;
		$guards_done = false;

		foreach ( $attachments as $item ) {
			if ( ! is_array( $item ) || ! array_key_exists( 0, $item ) ) {
				continue;
			}

			$is_string   = ! empty( $item[5] );
			$raw_name    = isset( $item[2] ) ? (string) $item[2] : '';
			$name        = '' !== $raw_name ? $raw_name : ( isset( $item[1] ) ? (string) $item[1] : '' );
			$name        = sanitize_file_name( $name );
			$type        = isset( $item[4] ) ? (string) $item[4] : '';
			$encoding    = isset( $item[3] ) ? (string) $item[3] : '';
			$disposition = ( isset( $item[6] ) && 'inline' === $item[6] ) ? 'inline' : 'attachment';
			$cid         = ( 'inline' === $disposition && ! empty( $item[7] ) && is_string( $item[7] ) ) ? (string) $item[7] : '';

			if ( '' === $name ) {
				$name = $is_string ? 'attachment' : wp_basename( (string) $item[0] );
				$name = sanitize_file_name( $name );
			}

			$content = self::readContent( $item[0], $is_string );
			if ( false === $content ) {
				$meta[] = self::metaEntry( $name, '', '', 0, $type, $encoding, $disposition, $cid, 'missing' );
				continue;
			}

			$size = strlen( $content );
			$ext  = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );

			if ( $size > $max_file || ( $total_bytes + $size ) > $max_total ) {
				$meta[] = self::metaEntry( $name, '', $ext, $size, $type, $encoding, $disposition, $cid, 'too_large' );
				continue;
			}

			$hash = hash( 'sha256', $content );
			$path = self::pathForHash( $hash, $ext );
			if ( '' === $path ) {
				$meta[] = self::metaEntry( $name, '', $ext, $size, $type, $encoding, $disposition, $cid, 'error' );
				continue;
			}

			// Already in the store: reuse it, no second copy.
			if ( ! is_file( $path ) ) {
				if ( ! $guards_done ) {
					self::writeGuards();
					$guards_done = true;
				}
				if ( ! self::putFile( $path, $content ) ) {
					$meta[] = self::metaEntry( $name, '', $ext, $size, $type, $encoding, $disposition, $cid, 'error' );
					continue;
				}
			}

			$total_bytes += $size;
			$meta[]       = self::metaEntry( $name, $hash, $ext, $size, $type, $encoding, $disposition, $cid, '' );
		}

		return $meta;
	}

	/**
	 * Resolve a log's stored metadata into ready-to-attach file specs, keeping
	 * only entries whose shared copy still exists and is readable.
	 *
	 * @param int   $log_id Email log id (used for the legacy per-log layout).
	 * @param mixed $meta   Value of the log row's `attachments` column.
	 * @return array<int,array<string,string>> Specs: path, name, encoding, type, disposition, cid.
	 */
	public static function getForResend( $log_id, $meta ) {
		$meta = maybe_unserialize( $meta );
		if ( ! is_array( $meta ) || empty( $meta ) ) {
			return array();
		}

		$legacy_dir = self::legacyLogDir( $log_id );
		$specs      = array();

		foreach ( $meta as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$path = '';
			if ( ! empty( $entry['hash'] ) ) {
				$path = self::pathForHash( $entry['hash'], isset( $entry['ext'] ) ? $entry['ext'] : '' );
			} elseif ( ! empty( $entry['stored'] ) && '' !== $legacy_dir ) {
				$path = $legacy_dir . basename( (string) $entry['stored'] );
			}

			if ( '' === $path || ! is_file( $path ) || ! is_readable( $path ) ) {
				continue;
			}

			$specs[] = array(
				'path'        => $path,
				'name'        => ! empty( $entry['name'] ) ? (string) $entry['name'] : wp_basename( $path ),
				'encoding'    => ! empty( $entry['encoding'] ) ? (string) $entry['encoding'] : 'base64',
				'type'        => isset( $entry['type'] ) ? (string) $entry['type'] : '',
				'disposition' => ( isset( $entry['disposition'] ) && 'inline' === $entry['disposition'] ) ? 'inline' : 'attachment',
				'cid'         => isset( $entry['cid'] ) ? (string) $entry['cid'] : '',
			);
		}

		return $specs;
	}

	/**
	 * Slim list for the email log detail screen.
	 *
	 * @param mixed $meta Value of the log row's `attachments` column.
	 * @return array<int,array<string,mixed>> Entries with keys: name, size, stored.
	 */
	public static function getForDisplay( $meta ) {
		$meta = maybe_unserialize( $meta );
		if ( ! is_array( $meta ) || empty( $meta ) ) {
			return array();
		}

		$list = array();
		foreach ( $meta as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$list[] = array(
				'name'   => isset( $entry['name'] ) ? (string) $entry['name'] : '',
				'size'   => isset( $entry['size'] ) ? (int) $entry['size'] : 0,
				'stored' => ! empty( $entry['hash'] ) || ! empty( $entry['stored'] ),
			);
		}

		return $list;
	}

	/**
	 * Release the attachments referenced by the given email log ids, deleting a
	 * shared file only once no remaining log points at its hash.
	 *
	 * IMPORTANT: call this BEFORE the log rows are removed from the DB - it needs
	 * to read their `attachments` column to know which hashes to check.
	 *
	 * @param array|string $log_ids Ids as array or comma separated string.
	 */
	public static function delete( $log_ids ) {
		if ( ! is_array( $log_ids ) ) {
			$log_ids = explode( ',', (string) $log_ids );
		}
		$log_ids = array_values( array_unique( array_filter( array_map( 'absint', $log_ids ) ) ) );
		if ( empty( $log_ids ) ) {
			return;
		}

		// Meant for bounded id sets (user selections). Range / "delete all"
		// paths use cleanupOrphans() / deleteAll() instead.
		if ( count( $log_ids ) <= 1000 && self::schemaReady() ) {
			global $wpdb;

			$table = $wpdb->prefix . 'yaysmtp_email_logs';
			// $log_ids is already a list of positive integers (absint above), so
			// it is safe to interpolate straight into the IN () clause.
			$id_list = implode( ',', $log_ids );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- integer id list, no user input.
			$rows = $wpdb->get_col( "SELECT attachments FROM {$table} WHERE id IN ( {$id_list} ) AND attachments IS NOT NULL AND attachments <> ''" );

			// Collect the (hash, ext) pairs those logs referenced.
			$candidates = array();
			foreach ( $rows as $row ) {
				foreach ( self::extractHashes( $row ) as $hash => $ext ) {
					$candidates[ $hash ] = $ext;
				}
			}

			// Drop a shared file only if no OTHER log still references its hash.
			foreach ( $candidates as $hash => $ext ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- integer id list; hash is passed as a bound LIKE parameter.
				$still_used = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id NOT IN ( {$id_list} ) AND attachments LIKE %s LIMIT 1", '%' . $wpdb->esc_like( $hash ) . '%' ) );
				if ( empty( $still_used ) ) {
					self::removeHashFile( $hash, $ext );
				}
			}
		}

		// Legacy per-log folders from the first version of this feature.
		foreach ( $log_ids as $log_id ) {
			$legacy = self::legacyLogDir( $log_id );
			if ( '' !== $legacy && is_dir( untrailingslashit( $legacy ) ) ) {
				self::rrmdir( $legacy );
			}
		}
	}

	/**
	 * Remove every stored attachment (used by "delete all logs" and uninstall).
	 */
	public static function deleteAll() {
		$root = self::getRootDir();
		if ( '' !== $root && is_dir( untrailingslashit( $root ) ) ) {
			self::rrmdir( $root );
		}
	}

	/**
	 * Delete shared files that no email log references any more. Safety net for
	 * deletions that bypass the plugin's own handlers (retention cron, direct
	 * SQL, ...). Also clears leftover legacy per-log folders.
	 */
	public static function cleanupOrphans() {
		$root = self::getRootDir();
		if ( '' === $root || ! is_dir( untrailingslashit( $root ) ) ) {
			return;
		}

		global $wpdb;

		$schema_ready = self::schemaReady();

		// Content addressed files: {root}/{ab}/{hash}.{ext}
		foreach ( (array) glob( $root . '*', GLOB_ONLYDIR ) as $sub ) {
			$base = basename( $sub );

			// Legacy per-log folder (numeric): drop when its log is gone.
			if ( ctype_digit( $base ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off cleanup lookup.
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}yaysmtp_email_logs WHERE id = %d", absint( $base ) ) );
				if ( empty( $exists ) ) {
					self::rrmdir( trailingslashit( $sub ) );
				}
				continue;
			}

			// Without the metadata column we cannot tell which shared files are
			// still referenced, so leave them alone.
			if ( ! $schema_ready || ! preg_match( '/^[a-f0-9]{2}$/', $base ) ) {
				continue;
			}

			$entries = @scandir( $sub ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
			foreach ( ( false === $entries ? array() : $entries ) as $name ) {
				if ( '.' === $name || '..' === $name ) {
					continue;
				}
				$file = trailingslashit( $sub ) . $name;
				if ( ! is_file( $file ) ) {
					continue;
				}

				// Stale temp file from an interrupted write (older than an hour).
				if ( 0 === strpos( $name, '.tmp-' ) ) {
					if ( ( time() - (int) @filemtime( $file ) ) > HOUR_IN_SECONDS ) {
						@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
					}
					continue;
				}

				$hash = strtolower( (string) pathinfo( $file, PATHINFO_FILENAME ) );
				if ( ! preg_match( '/^[a-f0-9]{16,}$/', $hash ) ) {
					continue;
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off cleanup lookup.
				$used = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}yaysmtp_email_logs WHERE attachments LIKE %s LIMIT 1", '%' . $wpdb->esc_like( $hash ) . '%' ) );
				if ( empty( $used ) ) {
					@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- best-effort cleanup.
				}
			}

			// Remove the shard folder if it ended up empty.
			if ( self::isDirEmpty( $sub ) ) {
				@rmdir( $sub ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
			}
		}
	}

	/**
	 * Build a metadata entry.
	 *
	 * @return array<string,mixed>
	 */
	private static function metaEntry( $name, $hash, $ext, $size, $type, $encoding, $disposition, $cid, $skipped ) {
		$entry = array(
			'name'        => $name,
			'hash'        => $hash,
			'ext'         => $ext,
			'size'        => (int) $size,
			'type'        => $type,
			'encoding'    => '' !== $encoding ? $encoding : 'base64',
			'disposition' => $disposition,
			'cid'         => $cid,
		);
		if ( '' !== $skipped ) {
			$entry['skipped'] = $skipped;
		}
		return $entry;
	}

	/**
	 * Pull the hash => ext map out of a serialized `attachments` column value.
	 *
	 * @param string $raw Column value.
	 * @return array<string,string>
	 */
	private static function extractHashes( $raw ) {
		$meta = maybe_unserialize( $raw );
		if ( ! is_array( $meta ) ) {
			return array();
		}
		$out = array();
		foreach ( $meta as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['hash'] ) ) {
				$hash = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $entry['hash'] ) );
				if ( '' !== $hash ) {
					$out[ $hash ] = isset( $entry['ext'] ) ? (string) $entry['ext'] : '';
				}
			}
		}
		return $out;
	}

	/**
	 * Delete one shared file and prune its shard folder if empty.
	 *
	 * @param string $hash Content hash.
	 * @param string $ext  Extension without dot.
	 */
	private static function removeHashFile( $hash, $ext ) {
		$path = self::pathForHash( $hash, $ext );
		if ( '' === $path ) {
			return;
		}
		if ( is_file( $path ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
		}
		$shard = dirname( $path );
		if ( self::isWithinRoot( $shard ) && self::isDirEmpty( $shard ) ) {
			@rmdir( $shard ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
		}
	}

	/**
	 * Read an attachment's raw bytes from a file path or return the inline string.
	 *
	 * @param string $source    File path or raw content.
	 * @param bool   $is_string Whether $source is already the content.
	 * @return string|false
	 */
	private static function readContent( $source, $is_string ) {
		if ( $is_string ) {
			return is_string( $source ) ? $source : false;
		}
		$source = (string) $source;
		if ( '' === $source || ! is_file( $source ) || ! is_readable( $source ) ) {
			return false;
		}
		$content = file_get_contents( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return ( false === $content ) ? false : $content;
	}

	/**
	 * Write the deny/index guard files on the attachments root.
	 */
	private static function writeGuards() {
		$root = self::getRootDir();
		if ( '' === $root ) {
			return;
		}
		if ( ! is_dir( untrailingslashit( $root ) ) ) {
			wp_mkdir_p( $root );
		}
		if ( ! is_file( $root . 'index.html' ) ) {
			@file_put_contents( $root . 'index.html', '' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
		}
		if ( ! is_file( $root . '.htaccess' ) ) {
			$rules  = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
			$rules .= "<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n";
			@file_put_contents( $root . '.htaccess', $rules ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
		}
	}

	/**
	 * Atomically write content to $path, creating its shard folder as needed.
	 *
	 * @param string $path    Target absolute path.
	 * @param string $content Raw bytes.
	 * @return bool
	 */
	private static function putFile( $path, $content ) {
		$dir = dirname( $path );
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$tmp = $dir . '/.tmp-' . wp_generate_password( 12, false, false );
		if ( false === file_put_contents( $tmp, $content ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
			return false;
		}

		// rename() is atomic on the same filesystem and races harmlessly - the
		// bytes are identical whichever writer wins.
		if ( ! @rename( $tmp, $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
			return is_file( $path );
		}

		return true;
	}

	/**
	 * Legacy (v1) per-log folder path: {root}/{log_id}/
	 *
	 * @param int $log_id Email log id.
	 * @return string Trailing-slashed path, or ''.
	 */
	private static function legacyLogDir( $log_id ) {
		$log_id = absint( $log_id );
		$root   = self::getRootDir();
		if ( empty( $log_id ) || '' === $root ) {
			return '';
		}
		return trailingslashit( $root . $log_id );
	}

	/**
	 * Whether $path sits inside our attachments root.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	private static function isWithinRoot( $path ) {
		$root = untrailingslashit( self::getRootDir() );
		$path = untrailingslashit( $path );
		return '' !== $root && '' !== $path && 0 === strpos( $path . '/', $root . '/' );
	}

	/**
	 * Whether a directory has no entries other than dot files we manage.
	 *
	 * @param string $dir Directory path.
	 * @return bool
	 */
	private static function isDirEmpty( $dir ) {
		$items = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
		if ( false === $items ) {
			return false;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item || 'index.html' === $item ) {
				continue;
			}
			return false;
		}
		return true;
	}

	/**
	 * Recursively delete a directory, refusing to step outside the store root.
	 *
	 * @param string $dir Directory path.
	 */
	private static function rrmdir( $dir ) {
		$dir = untrailingslashit( $dir );
		if ( '' === $dir || ! is_dir( $dir ) || ! self::isWithinRoot( $dir ) ) {
			return;
		}

		$items = scandir( $dir );
		if ( false === $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( is_dir( $path ) && ! is_link( $path ) ) {
				self::rrmdir( $path );
			} else {
				@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
			}
		}

		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- best-effort filesystem cleanup.
	}
}
