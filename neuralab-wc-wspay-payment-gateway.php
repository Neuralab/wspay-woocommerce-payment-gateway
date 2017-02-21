<?php
/**
* Plugin Name: Neuralab WooCommerce WSPay Payment Gateway
* Plugin URI: https://neuralab.net
* Description: WooCommerce WSPay Payment Gateway
* Version: 0.8
* Author: Neuralab
* Author URI: https://neuralab.net
* Requires at least: 4.7
*/

if ( !defined( "ABSPATH" ) ) {
  exit;
}

/**
 * Return true if Woocommerce plugin is active
 * @return boolean
 */
function wcwspay_is_woocommerce_active() {
  if ( in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) ) {
    return true;
  }
  return false;
}

/**
 * Echo admin notice HTML for missing WooCommerce plugin
 */
function wcwspay_admin_notice_missing_woocommerce() {
  global $current_screen;
  if( $current_screen->parent_base === "plugins" ) {
    ?>
    <div class="notice notice-error">
      <p><?php _e( "Please install and activate <a href='http://www.woothemes.com/woocommerce/' target='_blank'>WooCommerce</a> before activating the Neuralab WooCommerce WSPay Payment Gateway!", "wcwspay" ); ?></p>
    </div>
    <?php
  }
}

if ( !wcwspay_is_woocommerce_active() ) {
  add_action( "admin_notices", "wcwspay_admin_notice_missing_woocommerce" );
  return;
}

if ( !class_exists( "WC_WSPay_Main" ) ) {
  class WC_WSPay_Main {
    const VERSION               = "0.8";
    protected static $instance  = null;

    /**
     * Class constructor
     */
    protected function __construct() {
      require_once( "includes/core/class-wc-wspay-payment-gateway.php" );
    }

    /**
     * Installation procedure
     */
    public static function install() {
      if ( !current_user_can( "activate_plugins" ) ) {
        return;
      }

    }

    /**
     * Uninstallation procedure
     */
    public static function uninstall() {
      if ( !current_user_can( "activate_plugins" ) ) {
	      return;
      }

      delete_option( "woocommerce_neuralab-wcwspay_settings" );
      wp_cache_flush();
    }

    /**
     * Return class instance
     * @return WC_WSPay_Main
     */
    public static function get_instance() {
      if ( is_null( self::$instance ) ) {
        self::$instance = new self;
      }
      return self::$instance;
    }

    /**
     * Cloning is forbidden
     */
    public function __clone() {
      _doing_it_wrong( __FUNCTION__, __( "Cloning is forbidden!", "wcwspay" ), "4.7" );
    }
    /**
     * Unserializing instances of this class is forbidden
     */
    public function __wakeup() {
      _doing_it_wrong( __FUNCTION__, __( "Unserializing instances is forbidden!", "wcwspay" ), "4.7" );
    }

  }
}

register_activation_hook( __FILE__, array( "WC_WSPay_Main", "install" ) );
register_uninstall_hook( __FILE__, array( "WC_WSPay_Main", "uninstall" ) );
add_action( "plugins_loaded", array( "WC_WSPay_Main", "get_instance" ), 0 );
