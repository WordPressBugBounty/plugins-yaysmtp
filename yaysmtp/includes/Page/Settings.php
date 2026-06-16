<?php
namespace YaySMTP\Page;

use YaySMTP\Helper\Utils;
use YaySMTP\Engines\Registries\ScriptName;
use YaySMTP\I18n;
use YaySMTP\Helper\LogErrors;

defined( 'ABSPATH' ) || exit;

class Settings {
	protected static $instance = null;

	private const PARENT              = 'yaycommerce';
	private const POSITION_KEY        = 'yaycommerce_admin_shell_submenu_positions';
	private const EMAIL_LOGS_SLUG     = 'yaysmtp#/email-logs';
	private const EMAIL_LOGS_POSITION   = 181;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->doHooks();
		}

		return self::$instance;
	}


	private function doHooks() {
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'network_admin_menu', array( $this, 'settingsNetWorkMenu' ), 180 );
		add_action( 'admin_menu', array( $this, 'addYaysmtpLogEmailMenu' ), 181 );
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueSmtpSettingsScripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminDashboardScripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueGlobalAdminScripts' ) );
		}
	}

	private function __construct() {}

	/**
	 * Place Email Logs directly after YaySMTP (180) in the shared admin-shell sort.
	 */
	private function registerEmailLogsSubmenuPosition(): void {
		if ( ! isset( $GLOBALS[ self::POSITION_KEY ] ) ) {
			$GLOBALS[ self::POSITION_KEY ] = [];
		}
		$GLOBALS[ self::POSITION_KEY ][ self::EMAIL_LOGS_SLUG ] = self::EMAIL_LOGS_POSITION;
	}

	public function settingsNetWorkMenu() {
		if ( $this->check_allow_smtp_settings_for_multisite() ) {
			$this->registerEmailLogsSubmenuPosition();
			add_submenu_page(
				self::PARENT,
				__( 'Email Logs', 'yay-smtp' ),
				__( '↳ Email Logs', 'yay-smtp' ),
				'manage_options',
				self::EMAIL_LOGS_SLUG,
				[ $this, 'emailLogsPage' ]
			);
		}

		remove_submenu_page( self::PARENT, self::PARENT );
	}

public function addYaysmtpLogEmailMenu() {
		if ( ! (is_multisite() && ! is_network_admin()  && 'yes' === Utils::getMainSiteMultisiteSetting() ) ) {
			$this->registerEmailLogsSubmenuPosition();
			add_submenu_page(
				self::PARENT,
				__( 'Email Logs', 'yay-smtp' ),
				__( '↳ Email Logs', 'yay-smtp' ),
				'manage_options',
				self::EMAIL_LOGS_SLUG,
				[ $this, 'emailLogsPage' ]
			);
		}
	}

	public function settingsPage() {
		echo '<div id="yaysmtp" class="yaysmtp-ui"></div>';

		$settings        = Utils::getYaySmtpSetting();
		if ( ! empty( $settings['smtp'] ) && ! empty( $settings['smtp']['pass'] ) ) {
			echo '<input type="hidden" value="' . esc_attr( Utils::decrypt( $settings['smtp']['pass'], 'smtppass' ) ) . '">';
		}
	}

	public function emailLogsPage() {
		echo '<script>
			(function () {
				const target = "#/email-logs";
				if (window.location.hash !== target) {
					window.location.replace(window.location.pathname + window.location.search + target);
				}
			})();
		</script>';
	}

	public function enqueueAdminDashboardScripts( $screenId ) {
		if ( $screenId !== 'index.php' ) {
			return;
		}

		wp_enqueue_style( 'yay_smtp_style', YAY_SMTP_PLUGIN_URL . 'assets/css/yaysmtp-dashboard-admin.css', array(), YAY_SMTP_VERSION );
		wp_enqueue_script( 'moment' );

		wp_enqueue_style( 'yay_smtp_daterangepicker', YAY_SMTP_PLUGIN_URL . 'assets/css/daterangepicker_custom.css', array(), YAY_SMTP_VERSION );
		wp_enqueue_script( 'yay_smtp_chart', YAY_SMTP_PLUGIN_URL . 'assets/js/chart.min.js', array(), YAY_SMTP_VERSION, true );
		wp_enqueue_script( 'yay_smtp_daterangepicker', YAY_SMTP_PLUGIN_URL . 'assets/js/daterangepicker_custom.min.js', array(), YAY_SMTP_VERSION, true );
		wp_enqueue_script( 'yay_smtp_other', YAY_SMTP_PLUGIN_URL . 'assets/js/other-smtp-admin.js', array(), YAY_SMTP_VERSION, true );

		wp_localize_script(
			'yay_smtp_other',
			'yaySmtpWpOtherData',
			array(
				'YAY_SMTP_PLUGIN_PATH' => YAY_SMTP_PLUGIN_PATH,
				'YAY_SMTP_PLUGIN_URL'  => YAY_SMTP_PLUGIN_URL,
				'YAY_SMTP_SITE_URL'    => YAY_SMTP_SITE_URL,
				'YAY_ADMIN_AJAX'       => admin_url( 'admin-ajax.php' ),
				'DASHBOARD_URL'   	   => get_dashboard_url(),
				'ajaxNonce'            => wp_create_nonce( 'ajax-nonce' ),
				'_cacheBust'           => time(),
			)
		);		
	}

	public function enqueueGlobalAdminScripts( $screenId ) {
		wp_enqueue_style( 'yay_smtp_global_style', YAY_SMTP_PLUGIN_URL . 'assets/css/yay-smtp-admin-global.css', array(), YAY_SMTP_VERSION );
		wp_enqueue_script( 'yay_smtp_global', YAY_SMTP_PLUGIN_URL . 'assets/js/global-smtp-admin.js', array(), YAY_SMTP_VERSION, true );

		wp_localize_script(
			'yay_smtp_global',
			'yaySmtpWpGlobalData',
			array(
				'YAY_ADMIN_AJAX'       => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'            => wp_create_nonce( 'ajax-nonce' ),
				'_cacheBust'           => time(),
			)
		);	
	}

	public function enqueueSmtpSettingsScripts( $screenId ) {
		if ( $screenId !== 'yaycommerce_page_yaysmtp' ) {
			return;
		}

		$succ_sent_mail_last = 'yes';
		$yaysmtpSettings     = Utils::getPublicYaySmtpSetting();
		if ( ! empty( $yaysmtpSettings ) && isset( $yaysmtpSettings['succ_sent_mail_last'] ) && false === $yaysmtpSettings['succ_sent_mail_last'] ) {
			$succ_sent_mail_last = 'no';
		}
		wp_enqueue_script( 'moment' );
		
		wp_enqueue_script( ScriptName::PAGE_SETTINGS );
		wp_enqueue_style( ScriptName::STYLE_SETTINGS );

		wp_enqueue_style( 'yay_smtp_daterangepicker', YAY_SMTP_PLUGIN_URL . 'assets/css/daterangepicker_custom.css', array(), YAY_SMTP_VERSION );
		wp_enqueue_script( 'yay_smtp_daterangepicker', YAY_SMTP_PLUGIN_URL . 'assets/js/daterangepicker_custom.min.js', array(), YAY_SMTP_VERSION, true );

		wp_localize_script(
			ScriptName::PAGE_SETTINGS,
			'yaySmtpWpData',
			array(
				'YAY_SMTP_PLUGIN_PATH' => YAY_SMTP_PLUGIN_PATH,
				'YAY_SMTP_PLUGIN_URL'  => YAY_SMTP_PLUGIN_URL,
				'YAY_SMTP_SITE_URL'    => YAY_SMTP_SITE_URL,
				'YAY_ADMIN_AJAX'       => admin_url( 'admin-ajax.php' ),
				'DASHBOARD_URL'   	   => get_dashboard_url(),
				'SECURE_AUTH_KEY' => defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : 'yay_smtp123098',
				'ajaxNonce'            => wp_create_nonce( 'ajax-nonce' ),
				'currentMailer'        => Utils::getCurrentMailer(),
				'yaysmtpSettings'      => $yaysmtpSettings,
				'yaysmtpLogSettings'   => Utils::getYaySmtpEmailLogSetting(),
				'succ_sent_mail_last'  => $succ_sent_mail_last,
				'is_multisite'         =>  is_multisite(),
				'is_network_admin'     => is_network_admin(),
				'is_multisite_mode'    => Utils::getMainSiteMultisiteSetting(),
				'i18n'                 => I18n::getTranslation(),
				'mailers'              => Utils::getAllMailer(),
				'amazonSesRegions'     => Utils::getAmazonSesRegions(),
				'zohoRegions'          => Utils::getZohoRegions(),
				'authUrl'              => [
					'gmail'              => Utils::getGmailAuthUrl(),
					'gmail_fallback' 	 => Utils::getGmailAuthUrl( true ),
					'outlookms'      	 => Utils::getOutlookMsAuthUrl(),
					'zoho'               => Utils::getZohoAuthUrl(),
				],
				'yayDebugText'         => [
					'normal' 	 => LogErrors::getErr(),
					'fallback'   => LogErrors::getErrFallback()
				],
				'importSettingsPluginList'  => Utils::getYaysmtpImportPlugins(),
				'importEmailLogsPluginList' => Utils::getEmailLogsImportPlugins(),
				'importedLogPluginList'     => Utils::getImportedLogPluginSetting(),
				'adminEmail' => Utils::getAdminEmail(),
				'adminName' => Utils::getAdminFromName(),
				'reviewed' => get_option( 'yaysmtp_reviewed', false ),
				'_cacheBust'           => time(),
			)
		);
	}

	public function admin_body_class( $classes ) {
		if ( strpos( $classes, 'yay-ui' ) === false ) {
			$classes .= ' yay-ui';
		}
		return $classes;
	}

	private function isYaysmtpAdminScreen( $screen_id ) {
		if ( is_string( $screen_id ) && false !== strpos( $screen_id, 'yaycommerce_page_yaysmtp' ) ) {
			return true;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return self::EMAIL_LOGS_SLUG === $page || 'yaysmtp' === $page;
	}

	private function check_allow_smtp_settings_for_multisite() {
		if ( is_multisite() && is_network_admin()  && 'yes' === Utils::getMainSiteMultisiteSetting() ) {
			return true;
		}

		return false;
	}
}
