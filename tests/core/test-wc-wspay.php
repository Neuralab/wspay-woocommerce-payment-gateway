<?php
/**
 * Class WC_WSPay_Test
 *
 * @package Neuralab_Wc_Wspay
 */

/**
 * @coversDefaultClass WC_WSPay
 */
class WC_WSPay_Test extends WP_UnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->wspay = new WC_WSPay();
  }

  public function tearDown() {
    unset( $this->wspay );
    parent::tearDown();
  }

  /**
   * @covers ::is_response_valid
   * @covers ::maybe_log
   */
  public function testIsResponseValid() {
    $order = WC_Helper_Order::create_order();
    $response = array();

    $this->wspay->logger = new WC_WSPay_Logger();

    $is_valid = $this->wspay->is_response_valid( $response, $order );
    $this->assertFalse( $is_valid, 'Response shouldn\'t be valid if it\'s empty.' );

    $response['Success'] = -1;
    $is_valid = $this->wspay->is_response_valid( $response, $order );
    $this->assertFalse( $is_valid, 'Response shouldn\'t be valid if the "success" is -1.' );

    $response['ErrorMessage'] = 'testing error message.';
    $is_valid = $this->wspay->is_response_valid( $response, $order );
    $this->assertFalse( $is_valid, 'Response shouldn\'t be valid if the "success" is -1.' );

    $response['ErrorMessage'] = 'ODBIJENO';
    $is_valid = $this->wspay->is_response_valid( $response, $order );
    $this->assertFalse( $is_valid, 'Response shouldn\'t be valid if the "success" is -1.' );

    $response['Success'] = 1;
    $is_valid = $this->wspay->is_response_valid( $response, $order );
    $this->assertTrue( $is_valid, 'Response should be valid if the "success" is 1.' );
  }

  /**
   * @covers ::get_request_url
   */
  public function testGetRequestUrl() {
    $request_url = $this->wspay->get_request_url( false );
    $this->assertNotFalse( filter_var( $request_url, FILTER_VALIDATE_URL ), 'get_request_url() should return valid URL.' );

    $request_url = $this->wspay->get_request_url( true );
    $this->assertNotFalse( filter_var( $request_url, FILTER_VALIDATE_URL ), 'get_request_url() should return valid URL.' );
  }

  /**
   * @covers ::get_wspay_params
   * @covers ::get_signature
   * @covers ::process_order_total
   */
  public function testGetWspayParams() {
    $order      = WC_Helper_Order::create_order();
    $gateway    = new WC_WSPay_Payment_Gateway();
    $shop_id    = 'fake-shop-id';
    $secret_key = 'fake-secret-key';
    $form_lang  = 'EN';
    $integrated_checkout  = false;

    $order->set_billing_address_2( 'Testing Billing Address 2' );
    $order->save();

    $url_params = array( 'ReturnURL', 'ReturnErrorURL', 'CancelURL' );
    $required_params = array_merge(
      $url_params,
      array( 'ShopID', 'ShoppingCartID', 'TotalAmount', 'Signature' )
    );

    $params = $this->wspay->get_wspay_params( $gateway->id, $order->get_id(), '', '', '', '' );
    $this->assertFalse( $params, 'get_wspay_params() should return false if invalid parameters provided.' );

    $params = $this->wspay->get_wspay_params( $gateway->id, $order->get_id(), $shop_id, $secret_key, $form_lang, $integrated_checkout );
    foreach ($required_params as $required_param) {
      $this->assertArrayHasKey( $required_param, $params, 'get_wspay_params() should return an array with "' . $required_param . '" key.' );
    }
    foreach ($url_params as $url_param) {
      $this->assertNotFalse( filter_var( $params[ $url_param ], FILTER_VALIDATE_URL ), '"' . $url_param . '" should be valid URL.' );
    }

    $this->assertTrue( $params['ShopID'] === $shop_id, '"ShopID" should contain provided shop ID.' );
    $this->assertTrue( $params['ShoppingCartID'] === $order->get_id(), '"ShoppingCartID" should be the same as order ID.' );
    // is valid md5 hash
    $this->assertRegExp( '/^[a-f0-9]{32}$/', $params['Signature'] );
    // is valid price format (xx,xx)
    $this->assertRegExp( '/^\d+,\d{1,2}$/', $params['TotalAmount'] );

    $integrated_checkout  = true;
	$params = $this->wspay->get_wspay_params( $gateway->id, $order->get_id(), $shop_id, $secret_key, $form_lang, $integrated_checkout );
	$this->assertTrue( $params['Iframe'] === 'True', '"Iframe" should be set to True.' );
	$this->assertTrue( $params['IframeResponseTarget'] === 'TOP', '"IframeResponseTarget" should be set to TOP.' );
  }
}
