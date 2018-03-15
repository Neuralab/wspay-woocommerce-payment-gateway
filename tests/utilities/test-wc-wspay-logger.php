<?php
/**
 * Class WC_WSPay_Logger_Test
 *
 * @package Neuralab_Wc_Wspay
 */

 /**
  * @coversDefaultClass \WC_WSPay_Logger
  */
class WC_WSPay_Logger_Test extends WP_UnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->test_mail    = 'neuralab@grr.la';
    $this->test_message = 'log mailer test message';
    $this->logger       = new WC_WSPay_Logger();
  }

  public function tearDown() {
    parent::tearDown();
  }

  private function getRandomLogLevel( $logger ) {
    $level_index = array_rand( $logger->log_levels, 1 );
    return $logger->log_levels[ $level_index ];
  }

  /**
   * @covers ::enable_mailer
   * @covers ::disable_mailer
   * @covers ::is_mailer_enabled
   */
  public function testEnableMailer() {
    $this->assertFalse( $this->logger->enable_mailer( '' ), 'Method should not accept empty string as an e-mail.' );

    // should the invalid e-mail address be accepted?
    $message_fail = 'Method should not accept invalid e-mail address.';
    $this->assertFalse( $this->logger->enable_mailer( 'fake@mail' ), $message_fail );
    $this->assertFalse( $this->logger->enable_mailer( 'fake@mail.' ), $message_fail );
    $this->assertFalse( $this->logger->enable_mailer( '@mail.com' ), $message_fail );
    $this->assertFalse( $this->logger->is_mailer_enabled(), 'Mailer shouldn\'t be enabled with invalid e-mail address.' );

    // should the invalid log level be accepted?
    $this->assertFalse( $this->logger->enable_mailer( $this->test_mail, 'invalid_level' ), 'Mailer shouldn\'t be enabled with invalid log level.' );
    $this->assertFalse( $this->logger->is_mailer_enabled(), 'Mailer shouldn\'t be enabled with invalid log level.' );

    $level = $this->getRandomLogLevel( $this->logger );

    $this->assertTrue( $this->logger->enable_mailer( $this->test_mail, $level ), 'Mailer should be enabled with valid e-mail and log level.' );
    $this->assertTrue( $this->logger->is_mailer_enabled(), 'Mailer should be enabled with previously provided valid parameters.' );

    $this->logger->disable_mailer();
    $this->assertFalse( $this->logger->is_mailer_enabled(), 'Mailer should be disabled with disable_mailer() method.' );
  }

  /**
   * @covers ::log
   * @covers ::toggle_logger
   */
  public function testLog() {
    $level = $this->getRandomLogLevel( $this->logger );

    $this->assertFalse( $this->logger->log( $this->test_message, $level ), 'Should return false if logger is disabled.' );
    $this->logger->toggle_logger( true );

    $invalid_level = 'invalid level';
    $this->assertTrue( $this->logger->log( $this->test_message, $invalid_level ), 'Should return true although invalid level is provided.' );

    $this->logger->enable_mailer( $this->test_mail, $level );
    $this->assertTrue( $this->logger->log( $this->test_message, $level ), 'Should return true if provided valid message and level.' );
  }

  /**
   * @covers ::send_mail
   */
  public function testSendMail() {
    $level = $this->getRandomLogLevel( $this->logger );
    $invalid_level = 'invalid level';

    $this->logger->enable_mailer( '', $level );
    $result = invokePrivateMethod( $this->logger, 'send_mail', array( $level, $this->test_message ) );
    $this->assertFalse( $result, 'Mail shouldn\'t be sent if there\'s no defined mailer address.' );

    $this->logger->enable_mailer( $this->test_mail, $level );

    $result = invokePrivateMethod( $this->logger, 'send_mail', array( $invalid_level, $this->test_message ) );
    $this->assertFalse( $result, 'Mail shouldn\'t be sent if we provide invalid level.' );

    $result = invokePrivateMethod( $this->logger, 'send_mail', array( $level, $this->test_message ) );
    $this->assertTrue( $result, 'Mail should be sent with valid level and message.' );
  }

}
