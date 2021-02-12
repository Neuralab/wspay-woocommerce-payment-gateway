<?php
/**
 * Plugin Name: Neuralab WooCommerce WSPay Payment Gateway
 * Plugin URI: https://github.com/Neuralab/WSPay-WooCommerce-Payment-Gateway
 * Description: WooCommerce WSPay Payment Gateway
 * Version: 0.9.3
 * Author: Neuralab
 * Author URI: https://neuralab.net
 * Developer: Matej
 * Text Domain: wcwspay
 * Requires at least: 4.7
 * Requires PHP: 5.6
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5
 *
 * License: MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! function_exists( 'wcwspay_is_woocommerce_active' ) ) {
	/**
	 * Return true if Woocommerce plugin is active.
	 *
	 * @since 0.1
	 * @return boolean
	 */
	function wcwspay_is_woocommerce_active() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'wcwspay_admin_notice_missing_woocommerce' ) ) {
	/**
	 * Echo admin notice HTML for missing WooCommerce plugin.
	 *
	 * @since 0.1
	 */
	function wcwspay_admin_notice_missing_woocommerce() {
		global $current_screen;
		if ( $current_screen->parent_base === 'plugins' ) {
			?>
			<div class="notice notice-error">
				<p><?php _e( 'Please install and activate <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> before activating the Neuralab WooCommerce WSPay Payment Gateway!', 'wcwspay' ); ?></p>
			</div>
			<?php
		}
	}
}

if ( ! wcwspay_is_woocommerce_active() ) {
	add_action( 'admin_notices', 'wcwspay_admin_notice_missing_woocommerce' );
	return;
}

if ( ! class_exists( 'WC_WSPay_Main' ) ) {
	class WC_WSPay_Main {
		/**
		 * Current plugin's version.
		 *
		 * @var string
		 */
		const VERSION = '0.9.3';

		/**
		 * Instance of the current class, null before first usage.
		 *
		 * @var WC_WSPay_Main
		 */
		protected static $instance = null;

		/**
		 * Class constructor.
		 *
		 * @codeCoverageIgnore
		 * @since 0.1
		 */
		protected function __construct() {
			require_once( 'includes/core/class-wc-wspay-payment-gateway.php' );
		}

		/**
		 * Installation procedure.
		 *
		 * @static
		 * @since 0.1
		 */
		public static function install() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}
		}

		/**
		 * Uninstallation procedure.
		 *
		 * @static
		 * @since 0.1
		 */
		public static function uninstall() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			delete_option( 'woocommerce_neuralab-wcwspay_settings' );
			wp_cache_flush();
		}

		/**
		 * Return class instance.
		 *
		 * @static
		 * @since 0.1
		 * @return WC_WSPay_Main
		 */
		public static function get_instance() {
			// @codeCoverageIgnoreStart
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			// @codeCoverageIgnoreEnd
			return self::$instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 0.1
		 */
		public function __clone() {
			return wp_die( 'Cloning is forbidden!' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 0.1
		 */
		public function __wakeup() {
			return wp_die( 'Unserializing instances is forbidden!' );
		}
	}
}

register_activation_hook( __FILE__, [ 'WC_WSPay_Main', 'install' ] );
register_uninstall_hook( __FILE__, [ 'WC_WSPay_Main', 'uninstall' ] );
add_action( 'plugins_loaded', [ 'WC_WSPay_Main', 'get_instance' ], 0 );
