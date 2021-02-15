<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WC_WSPay' ) ) {
	class WC_WSPay {
		/**
		 * Config object, should contain all the data for the choosen env.
		 *
		 * @var WC_WSPay_Config
		 */
		private $config = null;

		/**
		 * Init WSPay settings.
		 *
		 * @codeCoverageIgnore
		 * @param WC_WSPay_Logger $logger: defaults to null
		 */
		public function __construct( $logger = null ) {
			$dir_path = dirname( plugin_dir_path( __FILE__ ) );
			require_once $dir_path . '/utilities/class-wc-wspay-config.php';
			require_once $dir_path . '/utilities/class-wc-wspay-logger.php';

			$this->config = new WC_WSPay_Config();
			if ( is_a( $logger, 'WC_WSPay_Logger' ) ) {
				$this->logger = $logger;
			}
		}

		/**
		 * Check response for any error messages or invalid status. Return false
		 * in case of error.
		 *
		 * @param array    $response
		 * @param WC_Order $order
		 * @return boolean
		 */
		public function is_response_valid( $response, $order ) {
			if ( ! isset( $response['Success'] ) || intval( $response['Success'] ) !== 1 ) {
				if ( ! isset( $response['ErrorMessage'] ) ) {
					$this->maybe_log( 'Payment for Order #' . $order->get_order_number() . ' unsuccesful. Reason: unknown, missing error message.', 'error' );
					$order->update_status( 'pending', __( 'Payment unsuccesful', 'wcwspay' ) );

					if ( function_exists( 'wc_add_notice' ) ) {
						wc_add_notice( __( 'Payment unsuccesful, reason unknown!', 'wcwspay' ) . '! ' . __( 'Try again or contact site administrator.' ), $notice_type = 'error' );
					}
				} elseif ( strtoupper( $response['ErrorMessage'] ) === 'ODBIJENO' ) {
					$this->maybe_log( 'Payment for Order #' . $order->get_order_number() . ' denied.', 'error' );
					$order->update_status( 'pending', __( 'Payment denied', 'wcwspay' ) );

					if ( function_exists( 'wc_add_notice' ) ) {
						wc_add_notice( __( 'Payment for your order is rejected!', 'wcwspay' ) . ' ' . __( 'Please contact site administrator.', 'wcwspay' ), $notice_type = 'error' );
					}
				} else {
					$this->maybe_log( 'Payment for Order #' . $order->get_order_number() . ' unsuccesful. Reason: ' . $response['ErrorMessage'], 'error' );
					/* translators: %s: error message */
					$order->update_status( 'pending', sprintf( esc_html__( 'Payment unsuccesful! Reason: %s', 'wcwspay' ), $response['ErrorMessage'] ) );
					if ( function_exists( 'wc_add_notice' ) ) {
						wc_add_notice( __( 'Payment unsuccesful', 'wcwspay' ) . '! ' . __( 'Try again or contact site administrator.' ), $notice_type = 'error' );
					}
				}
				return false;
			}
			return true;
		}

		/**
		 * Return testing or production request URL.
		 *
		 * @param boolean $is_testing_mode
		 * @return string
		 */
		public function get_request_url( $is_testing_mode ) {
			if ( $is_testing_mode ) {
				return $this->config->get( 'test_request_url' );
			} else {
				return $this->config->get( 'production_request_url' );
			}
		}

		/**
		 * Generate and return WSPay params array or return false in case of failure.
		 *
		 * @param string $gateway_id  should be something like 'neuralab-wcwspay'
		 * @param int    $order_id
		 * @param string $shop_id
		 * @param string $secret_key
		 * @param string $form_lang
		 * @return array|boolean
		 */
		public function get_wspay_params( $gateway_id, $order_id, $shop_id, $secret_key, $form_lang ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );

			$return_url  = $woocommerce->api_request_url( $gateway_id );
			$order_total = $this->process_order_total( $order );

			$signature = $this->get_signature( $order_id, $order_total, $shop_id, $secret_key );
			if ( empty( $signature ) || ! is_string( $signature ) ) {
				$this->maybe_log( 'Invalid signature', 'error' );
				return false;
			}

			$wspay_params = [
				'ShopID'            => $shop_id,
				'ShoppingCartID'    => $order_id,
				'TotalAmount'       => $order_total,
				'Signature'         => $signature,
				'ReturnURL'         => $return_url,
				'ReturnErrorURL'    => $return_url,
				'CancelURL'         => $order->get_cancel_order_url_raw(),
				// optionals parameters:
				'Lang'              => $form_lang,
				'CustomerFirstName' => $order->get_billing_first_name(),
				'CustomerLastName'  => $order->get_billing_last_name(),
				'CustomerAddress'   => $order->get_billing_address_1(),
				'CustomerCity'      => $order->get_billing_city(),
				'CustomerZIP'       => $order->get_billing_postcode(),
				'CustomerCountry'   => $order->get_billing_country(),
				'CustomerEmail'     => $order->get_billing_email(),
				'CustomerPhone'     => $order->get_billing_phone(),
			];

			if ( ! empty( $order->get_billing_address_2() ) ) {
				$wspay_params['CustomerAddress'] .= ', ' . $order->get_billing_address_2();
			}

			return $wspay_params;
		}

		/**
		 * Generate and return md5 encrypted signature.
		 * Signature is generated from the following string:
		 * shop_id + secret_key + order_id + secret_key + order_total + secret_key
		 *
		 * @param int    $order_id
		 * @param string $order_total
		 * @param string $shop_id
		 * @param string $secret_key
		 * @return mixed encrypted string or false in case of failure
		 */
		private function get_signature( $order_id, $order_total, $shop_id, $secret_key ) {
			// total amount must be striped from any dots and/or commas
			$order_total = str_replace( [ ',', '.' ], '', $order_total );

			if ( empty( $shop_id ) || empty( $secret_key ) || empty( $order_id ) || empty( $order_total ) ) {
				$this->maybe_log( 'Missing data for generating encrypted signature', 'error' );
				return false;
			}

			return md5( $shop_id . $secret_key . $order_id . $secret_key . $order_total . $secret_key );
		}

		/**
		 * Compares the given incoming signature with the one generated from the
		 * rest of the parameters. Returns true if they match or false otherwise.
		 *
		 * @codeCoverageIgnore
		 * @param string $incoming_signature
		 * @param int    $order_id
		 * @param string $shop_id
		 * @param string $secret_key
		 * @param int    $success
		 * @param string $approval_code
		 * @return boolean
		 */
		public function is_incoming_signature_valid( $incoming_signature, $order_id, $shop_id, $secret_key, $success, $approval_code ) {
			$new_signature = md5( $shop_id . $secret_key . $order_id . $secret_key . $success . $secret_key . $approval_code . $secret_key );
			if ( $incoming_signature === $new_signature ) {
				return true;
			}
			return false;
		}

		/**
		 * Return order's total amount in right format for WSPay.
		 *
		 * @param WC_Order $order
		 * @return string
		 */
		private function process_order_total( $order ) {
			$order_total = explode( '.', $order->get_total() );
			$decimal     = ! isset( $order_total[1] ) || empty( $order_total[1] ) ? '00' : $order_total[1];

			return $order_total[0] . ',' . $decimal;
		}

		/**
		 * Create log entry if logger is defined.
		 *
		 * @param  string $message
		 * @param  string $level
		 */
		private function maybe_log( $message, $level = 'info' ) {
			if ( property_exists( $this, 'logger' ) ) {
				$this->logger->log( $message, $level );
			}
		}

	}
}
