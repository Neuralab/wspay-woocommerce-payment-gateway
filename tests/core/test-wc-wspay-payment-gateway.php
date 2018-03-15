<?php
/**
 * Class WC_WSPay_Payment_Gateway_Test
 *
 * @package Neuralab_Wc_Wspay
 */

/**
 * @coversDefaultClass WC_WSPay_Payment_Gateway
 */
class WC_WSPay_Payment_Gateway_Test extends WP_UnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->gateway = new WC_WSPay_Payment_Gateway();
  }

  public function tearDown() {
    unset( $this->gateway );
    parent::tearDown();
  }

  /**
   * @covers ::init_form_fields
   */
  public function testInitFormFields() {
    unset( $this->gateway->form_fields );

    $this->gateway->init_form_fields();
    $this->assertInternalType( 'array', $this->gateway->form_fields, 'Form fields should be an array.' );
    $this->assertNotEmpty( $this->gateway->form_fields, 'Form fields shouldn,\'t be empty.' );
  }

  /**
   * @covers ::init_settings
   */
  public function testInitSettings() {
    unset( $this->gateway->settings );
    $this->gateway->init_settings();
    $this->assertInternalType( 'array', $this->gateway->settings, 'Settings should be an array.' );
    $this->assertNotEmpty( $this->gateway->settings, 'Settings shouldn\'t be empty.' );
  }

  /**
   * @covers ::admin_options
   */
  public function testAdminOptions() {
    ob_start();
    $this->gateway->admin_options();
    $result = ob_get_clean();

    $this->assertInternalType( 'string', $result, 'Admin options should be a string.' );
    $this->assertRegExp( '/<\s?[^\>]*\/?\s?>/i', $result, 'Admin options should contain HTML tags.' );
  }

  /**
   * @covers ::payment_fields
   */
  public function testPaymentFields() {
    $this->gateway->settings['description-msg'] = 'test description';
    ob_start();
    $this->gateway->payment_fields();
    $result = ob_get_clean();

    $this->gateway->settings['description-msg'] = '';
    $this->assertNotEmpty( $result, 'Payment fields should output non-empty string.' );
  }

  /**
   * @covers ::show_confirmation_message
   */
  public function testShowConfirmationMessage() {
    $this->gateway->settings['confirmation-msg'] = 'test confirmation message';
    ob_start();
    $this->gateway->show_confirmation_message();
    $result = ob_get_clean();

    $this->gateway->settings['confirmation-msg'] = '';
    $this->assertNotEmpty( $result, 'Confirmation message should be non-empty string.' );
  }

  /**
   * @covers ::show_receipt_message
   */
  public function testShowReceiptMessage() {
    $this->gateway->settings['receipt-redirect-msg'] = 'test receipt redirect message';
    ob_start();
    invokePrivateMethod( $this->gateway, 'show_receipt_message' );
    $result = ob_get_clean();

    $this->gateway->settings['receipt-redirect-msg'] = '';
    $this->assertNotEmpty( $result, 'Receipt redirect message should be non-empty string.' );
  }

  /**
   * @covers ::do_receipt_page
   * @covers ::enqueue_redirect_js
   * @covers ::get_params_form
   */
  public function testDoReceiptPage() {
    $order = WC_Helper_Order::create_order();

    ob_start();
    $this->gateway->do_receipt_page( $order->get_id() );
    $false_result = ob_get_clean();
    $this->assertEmpty( $false_result, 'do_receipt_page() method should echo empty string without appropriate settings.' );

    $this->gateway->settings['shop-id']    = 'fake-shop-id';
    $this->gateway->settings['secret-key'] = 'fake-secret-key';

    $this->gateway->settings['auto-redirect'] = 'no';
    ob_start();
    $this->gateway->do_receipt_page( $order->get_id() );
    $result = ob_get_clean();

    $this->gateway->settings['auto-redirect'] = 'yes';
    ob_start();
    $this->gateway->do_receipt_page( $order->get_id() );
    $result = ob_get_clean();

    $this->assertInternalType( 'string', $result, 'do_receipt_page() method should echo a string.' );
    $this->assertRegExp( '/<\s?[^\>]*\/?\s?>/i', $result, 'do_receipt_page() method should echo a HTML string.' );

    $this->assertStringStartsWith( '<form', $result, 'Receipt page form should start with <form> tag' );
    $this->assertStringEndsWith( '</form>', $result, 'Receipt page form should end with <form> tag' );
  }

  /**
   * @covers ::process_wspay_response
   */
  public function testProcessWspayResponse() {
    $order = WC_Helper_Order::create_order();

    $mocked_gateway = $this->getMockBuilder( 'WC_WSPay_Payment_Gateway' )
      ->setMethods( ['call_redirect'] )
      ->getMock();

    // mock call_redirect() which redirect user to given url.
    $mocked_gateway->method( 'call_redirect' )
      ->with( $this->equalTo( wc_get_checkout_url() ) );

    // try with different possible values of parameters
    $_GET = array();
    $this->assertNull( $mocked_gateway->process_wspay_response() );

    $_GET['Success'] = 1;
    $_GET['ShoppingCartID'] = -1;
    $this->assertNull( $mocked_gateway->process_wspay_response() );

    $_GET['Success'] = -1;
    $_GET['ShoppingCartID'] = $order->get_id();
    $this->assertNull( $mocked_gateway->process_wspay_response() );

    $_GET['Success'] = 1;
    $this->assertNull( $mocked_gateway->process_wspay_response() );

    $_GET['Signature'] = 'fake_signature';
    $_GET['ApprovalCode'] = 'fake_approval_code';

    // this should raise exception because incoming signature shouldn't be valid
    $exception_raised = false;
    try {
      $mocked_gateway->process_wspay_response();
    } catch ( Exception $e ) {
      $exception_raised = true;
    }
    $this->assertTrue( $exception_raised, 'Exception should be raised when incoming signature is invalid.' );

    // mock is_incoming_signature_valid() so that it always returns true
    $mocked_wspay = $this->getMockBuilder( 'WC_WSPay' )
      ->setMethods( ['is_incoming_signature_valid'] )
      ->getMock();

    $mocked_wspay->method( 'is_incoming_signature_valid' )
      ->willReturn( true );

    $mocked_gateway = $this->getMockBuilder( 'WC_WSPay_Payment_Gateway' )
      ->setMethods( ['call_redirect'] )
      ->getMock();

    $mocked_gateway->wspay = $mocked_wspay;
    $mocked_gateway->method( 'call_redirect' )
      ->with( $this->equalTo( $mocked_gateway->get_return_url($order) ) );

    // it's never really used because we have mocked is_incoming_signature_valid()
    $this->assertNull( $mocked_gateway->process_wspay_response() );
  }

  /**
   * @covers ::process_payment
   */
  public function testprocessPayment() {
    $order = WC_Helper_Order::create_order();
    $result = $this->gateway->process_payment($order->get_id());

    $this->assertArrayHasKey( 'result', $result, 'process_payment() should return an array with "result" key.' );
    $this->assertArrayHasKey( 'redirect', $result, 'process_payment() should return an array with "redirect" key.' );

    $this->assertNotEmpty( $result['result'], '"result" key shouldn\'t be empty.' );
    $this->assertNotFalse( filter_var( $result['redirect'], FILTER_VALIDATE_URL ), '"redirect" should contain valid URL.' );
  }

}
