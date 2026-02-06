<?php
namespace YaySMTP\Page;

use YaySMTP\Helper\Utils;
use YaySMTP\Engines\Registries\ScriptName;
use YaySMTP\I18n;
use YaySMTP\Helper\LogErrors;

defined( 'ABSPATH' ) || exit;

class Settings {
	protected static $instance = null;
	private $hook_suffix;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->doHooks();
		}

		return self::$instance;
	}


	private function doHooks() {
		$this->hook_suffix = array( 'yay_smtp_main_page' );
		add_action( 'admin_menu', array( $this, 'settingsMenu' ), YAYSMTP_MENU_PRIORITY );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'network_admin_menu', array( $this, 'settingsNetWorkMenu' ), YAYSMTP_MENU_PRIORITY );
		add_filter( 'plugin_action_links_' . YAY_SMTP_PLUGIN_BASENAME, array( $this, 'pluginActionLinks' ) );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueSmtpSettingsScripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminDashboardScripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueGlobalAdminScripts' ) );
		}
	}

	private function __construct() {}

	public function settingsMenu() {
		$this->hook_suffix['yay_smtp_main_page'] = add_submenu_page(
			'yaycommerce',
			__( 'YaySMTP Manager', 'yay-smtp' ),
			__( 'YaySMTP', 'yay-smtp' ),
			'manage_options',
			'yaysmtp',
			array( $this, 'settingsPage' ),
			0
		);
	}

	public function settingsNetWorkMenu() {
		$this->hook_suffix['yay_smtp_main_page'] = add_submenu_page(
			'yaycommerce',
			__( 'YaySMTP Manager', 'yay-smtp' ),
			__( 'YaySMTP', 'yay-smtp' ),
			'manage_options',
			'yaysmtp',
			array( $this, 'settingsPage' ),
			0
		);
	}

	public function pluginActionLinks( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=yaysmtp' ) . '" aria-label="' . esc_attr__( 'YaySMTP', 'yay-smtp' ) . '">' . esc_html__( 'Settings', 'yay-smtp' ) . '</a>',
		);
		return array_merge( $action_links, $links );
	}

	public function settingsPage() {
		echo '<div id="yaysmtp" class="yaysmtp-ui"></div>';

		$settings        = Utils::getYaySmtpSetting();
		if ( ! empty( $settings['smtp'] ) && ! empty( $settings['smtp']['pass'] ) ) {
			echo '<input type="hidden" value="' . esc_attr( Utils::decrypt( $settings['smtp']['pass'], 'smtppass' ) ) . '">';
		}
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
}
