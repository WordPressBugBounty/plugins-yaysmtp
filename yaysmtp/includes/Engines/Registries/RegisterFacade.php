<?php
namespace YaySMTP\Engines\Registries;

use YaySMTP\Engines\Registries\ScriptName;

/**
 * Register Facade.
 *
 * @method static RegisterFacade getInstance()
 */
class RegisterFacade {
    protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    /** Hooks Initialization */
    private function __construct() {
        add_filter( 'script_loader_tag', array( $this, 'add_entry_as_module' ), 10, 3 );
        add_action( 'init', array( $this, 'register_all_assets' ) );

        $is_prod = ! defined( 'YAY_SMTP_IS_DEVELOPMENT' ) || YAY_SMTP_IS_DEVELOPMENT !== true;
        if ( $is_prod && class_exists( '\YaySMTP\Engines\Registries\RegisterProd' ) ) {
            \YaySMTP\Engines\Registries\RegisterProd::getInstance();
        } elseif ( ! $is_prod && class_exists( '\YaySMTP\Engines\Registries\RegisterDev' ) ) {
            \YaySMTP\Engines\Registries\RegisterDev::getInstance();
        }
    }

    public function add_entry_as_module( $tag, $handle ) {
        if ( strpos( $handle, ScriptName::MODULE_PREFIX ) !== false ) {
            if ( strpos( $tag, 'type="' ) !== false ) {
                return preg_replace( '/\stype="\S+\s/', ' type="module" ', $tag, 1 );
            } else {
                return str_replace( ' src=', ' type="module" src=', $tag );
            }
        }
        return $tag;
    }

    public function register_all_assets() {
        wp_register_style( ScriptName::STYLE_SETTINGS, YAY_SMTP_PLUGIN_URL . 'assets/dist/style.css', array(), YAY_SMTP_VERSION );
    }

}
