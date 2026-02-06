<?php
namespace YaySMTP\Engines\Registries;

use YaySMTP\Engines\Registries\ScriptName;

/** Register in Production Mode */
class RegisterProd {
    protected static $instance = null;

    public static function getInstance() {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Hooks Initialization */
    private function __construct() {
        add_action( 'init', array( $this, 'register_all_scripts' ) );
    }

    public function register_all_scripts() {
        $deps = array( 'react', 'react-dom', 'wp-hooks', 'wp-i18n' );

        wp_register_script( ScriptName::PAGE_SETTINGS, YAY_SMTP_PLUGIN_URL . 'assets/dist/main.js', $deps, YAY_SMTP_VERSION, true );
    }
}
