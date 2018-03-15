<?php
/**
 * Class WC_WSPay_Main_Test
 *
 * @package Neuralab_Wc_Wspay
 */

/**
 * @coversDefaultClass WC_WSPay_Main
 */
class WC_WSPay_Main_Test extends WP_UnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * @covers ::get_instance
   */
  public function testGetInstance() {
    $is_instanced = true;
    try {
      $new_instance = new WC_WSPay_Main();
    } catch ( Error $e ) {
      $is_instanced = false;
    }
    $this->assertFalse( $is_instanced, 'Main class should be singleton.' );
    $this->assertInstanceOf( 'WC_WSPay_Main', WC_WSPay_Main::get_instance(), 'get_instance() should return instance of WC_WSPay_Main.' );
  }

  /**
   * @covers ::install
   * @covers ::uninstall
   */
  public function testInstallProcedures() {
    $main = WC_WSPay_Main::get_instance();

    $user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
    wp_set_current_user( $user_id );

    $this->assertFalse( $main->install(), 'User without "activate_plugins" capability shouldn\'t be able to install the plugin.' );
    $this->assertFalse( $main->uninstall(), 'User without "activate_plugins" capability shouldn\'t be able to uninstall the plugin.' );

    $user = wp_get_current_user();
    $user->add_cap( 'activate_plugins', true );

    $this->assertNull( $main->install(), 'User with "activate_plugins" capability should be able to install the plugin.' );
    $this->assertNull( $main->uninstall(), 'User with "activate_plugins" capability should be able to uninstall the plugin.' );
  }

  /**
   * @covers ::__clone
   * @covers ::__wakeup
   */
  public function testSecurity() {
    $main = WC_WSPay_Main::get_instance();

    $is_cloned = true;
    try {
      clone $main;
    } catch ( Exception $e ) {
      $is_cloned = false;
    }
    $this->assertFalse( $is_cloned, 'Main class cloning should throw exception.' );

    $is_unserialized = true;
    try {
      $serialized_main = serialize( $main );
      unserialize( $serialized_main );
    } catch ( Exception $e ) {
      $is_unserialized = false;
    }
    $this->assertFalse( $is_unserialized, 'Unserializing of the Main class is forbidden!.' );
  }

}
