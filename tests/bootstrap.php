<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Neuralab_Wc_Wspay
 */

require_once 'helpers.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
  $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
  echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
  exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
  $plugin_dir = dirname( dirname( __FILE__ ) );

  require $plugin_dir . '../../woocommerce/woocommerce.php';
  // woocommerce testing framework
  require $plugin_dir . '/tests/vendors/wc-framework/helpers/class-wc-helper-order.php';
  require $plugin_dir . '/tests/vendors/wc-framework/helpers/class-wc-helper-product.php';
  require $plugin_dir . '/tests/vendors/wc-framework/helpers/class-wc-helper-shipping.php';

  require $plugin_dir . '/includes/utilities/class-wc-wspay-config.php';
  require $plugin_dir . '/includes/utilities/class-wc-wspay-logger.php';
  require $plugin_dir . '/includes/core/class-wc-wspay-payment-gateway.php';
  require $plugin_dir . '/includes/core/class-wc-wspay.php';

  require $plugin_dir . '/neuralab-wc-wspay.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
