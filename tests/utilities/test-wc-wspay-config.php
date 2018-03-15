<?php
/**
 * Class WC_WSPay_Config_Test
 *
 * @package Neuralab_Wc_Wspay
 */

/**
 * @coversDefaultClass WC_WSPay_Config
 */
class WC_WSPay_Config_Test extends WP_UnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->config = new WC_WSPay_Config();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Tests all class methods, load_from_file() and get().
   *
   * @covers ::<public>
   */
  public function test() {
    $required_config_keys = array( 'test_request_url', 'production_request_url' );

    $this->assertFalse( $this->config->load_from_file( 'non_existing_file.json' ), 'load_from_file() should return false if non-existing file provided.' );
    $this->assertFalse( $this->config->get( $required_config_keys[0] ), 'If invalid file loaded, get() should return false.' );

    $this->assertTrue( $this->config->load_from_file(), 'Default param value is missing in load_from_file().' );
    $this->assertTrue( $this->config->load_from_file( 'config.json' ), 'config.json is missing or invalid.' );

    $this->assertFalse( $this->config->get( 'non-existing-param' ), 'get() should return false if non-existing parameter.' );

    foreach ( $required_config_keys as $config_key ) {
      $config_value = $this->config->get( $config_key );

      $this->assertNotFalse( $config_value, $config_value . ' not found in loaded config file.' );
      $this->assertTrue( is_string( $config_value ), $config_value . ' should be string.' );
      $this->assertNotFalse( filter_var( $config_value, FILTER_VALIDATE_URL ), $config_value . ' should be URL.' );
    }
  }

}
