<?php

if ( !defined( "ABSPATH" ) ) {
  exit;
}


if ( !class_exists( "WC_WSPay_Logger" ) ) {
  class WC_WSPay_Logger {

    /**
     * Whether or not logging is enabled.
     * @var boolean
     */
    public $is_log_enabled = false;

    /**
     * List of valid logger levels.
     * @var array
     */
    private $log_levels = [
      'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'
    ];

    /**
     * Init logger.
     */
    public function __construct($is_log_enabled = false) {
      $this->is_log_enabled = $is_log_enabled;
    }

    /**
     * Logs given message for given level and return true if successful, false
     * otherwise.
     * @param string $message
     * @param string $level: check $log_levels for valid level values.
     * @return boolean
     */
    public function log( $message, $level = "info" ) {
      if ( $this->is_log_enabled ) {
        if ( empty( $this->logger ) ) {
          if ( function_exists( "wc_get_logger" ) ) {
            $this->logger = wc_get_logger();
          } else {
            return false;
          }
        }
        // check if provided level is valid!
        if ( !in_array($level, $this->log_levels) ) {
          $this->logger->log( "debug", "Invalid log level provided: " . $level, array( "source" => "wcwspay" ) );
          $level = "notice";
        }
        $this->logger->log( $level, $message, array( "source" => "wcwspay" ) );

        return true;
      }

      return false;
    }

  }
}
