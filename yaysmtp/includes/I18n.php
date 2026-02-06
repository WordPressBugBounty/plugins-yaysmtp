<?php
namespace YaySMTP;

defined( 'ABSPATH' ) || exit;

class I18n {
	protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'loadPluginTextdomain' ) );
	}

	public function loadPluginTextdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}
		unload_textdomain( 'yay' );
		load_textdomain( 'yay-smtp', YAY_SMTP_PLUGIN_PATH . '/i18n/languages/yay-smtp-' . $locale . '.mo' );
		load_plugin_textdomain( 'yay-smtp', false, YAY_SMTP_PLUGIN_PATH . '/i18n/languages/' );
	}

	public static function getTranslation() {
		$translations = get_translations_for_domain( 'yay-smtp' );
        $messages     = [];

        $entries = $translations->entries;
        foreach ( $entries as $key => $entry ) {
            $messages[ $entry->singular ] = $entry->translations;
        }

		return $messages;
	}
}
