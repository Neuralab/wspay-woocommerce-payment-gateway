<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}


/**
 * Register payment gateway's class as a new method of payment.
 *
 * @param array $methods
 * @return array
 */
function wcwspay_add_gateway( $methods ) {
	$methods[] = 'WC_WSPay_Payment_Gateway';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'wcwspay_add_gateway' );

if ( ! class_exists( 'WC_WSPay_Payment_Gateway' ) ) {
	class WC_WSPay_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * WC_WSPay_Logger instance
		 *
		 * @var WC_WSPay_Logger
		 */
		private $logger = false;

		/**
		 * An array where key is the meta's name of the value in WC Order and value
		 * is a sub-array with next structure:
		 * [
		 *  'title'     => string,
		 *  'wspay_key' => string
		 * ]
		 *
		 * @var array
		 */
		protected $response_fields;

		/**
		 * Class constructor with basic gateway's setup.
		 *
		 * @codeCoverageIgnore
		 */
		public function __construct() {
			$dir_path = dirname( plugin_dir_path( __FILE__ ) );
			require_once 'class-wc-wspay.php';
			require_once $dir_path . '/utilities/class-wc-wspay-logger.php';

			$this->id              = 'neuralab-wcwspay';
			$this->method_title    = __( 'WSPay', 'wcwspay' );
			$this->has_fields      = true;
			$this->response_fields = [
				'wpsay_approval_code' => [
					'title'     => __( 'Approval code', 'wcwspay' ),
					'wspay_key' => 'ApprovalCode',
				],
				'wspay_eci'           => [
					'title'     => __( 'Electronic Commerce Indicator', 'wcwspay' ),
					'wspay_key' => 'ECI',
				],
				'wspay_success'       => [
					'title'     => __( 'Success Code', 'wcwspay' ),
					'wspay_key' => 'Success',
				],
				'wspay_datetime'      => [
					'title'     => __( 'Transaction date', 'wcwspay' ),
					'wspay_key' => 'DateTime',
				],
				'wspay_error_message' => [
					'title'     => __( 'Error message', 'wcwspay' ),
					'wspay_key' => 'ErrorMessage',
				],
				'wspay_order_id'      => [
					'title'     => __( 'WsPay\'s order ID', 'wcwspay' ),
					'wspay_key' => 'WsPayOrderId',
				],
			];

			$this->init_form_fields();
			$this->init_settings();

			$this->logger = new WC_WSPay_Logger( $this->settings['use-logger'] === 'yes' );
			if ( $this->settings['use-mailer'] === 'yes' ) {
				$this->logger->enable_mailer( $this->settings['mailer-address'], $this->settings['mailer-min-log-level'] );
			}
			$this->wspay = new WC_WSPay( $this->logger );

			$this->title = esc_attr( $this->settings['title'] );
			$this->add_actions();
		}

		/**
		 * Register different actions.
		 *
		 * @codeCoverageIgnore
		 */
		private function add_actions() {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
			add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'do_receipt_page' ] );
			add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'show_confirmation_message' ] );
			add_action( 'woocommerce_api_' . $this->id, [ $this, 'process_wspay_response' ] );
			add_action( 'woocommerce_thankyou', [ $this, 'show_additional_wspay_order_details' ] );
		}

		public function show_additional_wspay_order_details( $order_id ) {
			if ( ! apply_filters( 'show_order_wspay_details', false ) ) {
				return;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
				return;
			}
			?>
			<section class="wspay-order-details">
				<h2 class="woocommerce-column__title wspay-order-details__title">
					<?php esc_html_e( 'WSpay Order details', 'wcwspay' ); ?>
				</h2>
				<ul class="order_details">
					<?php foreach ( $this->response_fields as $key => $data ) : ?>
						<?php if ( apply_filters( 'show_order_detail_' . $key, true ) ) : ?>
							<?php $value = apply_filters( $key . '_order_details_value', $order->get_meta( $key, true ) ); ?>
							<?php if ( $value !== '' ) : ?>
								<li class="wspay-order-details__item <?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( apply_filters( $key . '_order_details_label', $data['title'] ) ); ?>:
									<strong><?php echo esc_html( $value ); ?></strong>
								</li>
							<?php endif; ?>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php
		}

		/**
		 * Define gateway's fields visible at WooCommerce's Settings page and
		 * Checkout tab.
		 *
		 * @override
		 */
		public function init_form_fields() {
			$this->form_fields = include 'wc-wspay-settings.php';
		}

		/**
		 * Echoes gateway's options (Checkout tab under WooCommerce's settings).
		 *
		 * @override
		 */
		public function admin_options() {
			echo '<h2>';
			esc_html_e( 'Neuralab\'s WSPay Payment Gateway', 'wcwspay' );
			echo '</h2>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		/**
		 * Display description of the gateway on the checkout page.
		 *
		 * @override
		 */
		public function payment_fields() {
			if ( isset( $this->settings['description-msg'] ) && ! empty( $this->settings['description-msg'] ) ) {
				echo esc_html( wptexturize( $this->settings['description-msg'] ) );
			}
		}

		/**
		 * Echo confirmation message on the 'thank you' page.
		 */
		public function show_confirmation_message() {
			if ( isset( $this->settings['confirmation-msg'] ) && ! empty( $this->settings['confirmation-msg'] ) ) {
				echo '<p>' . esc_html( wptexturize( $this->settings['confirmation-msg'] ) ) . '</p>';
			}
		}

		/**
		 * Echo redirect message on the 'receipt' page.
		 */
		private function show_receipt_message() {
			if ( isset( $this->settings['receipt-redirect-msg'] ) && ! empty( $this->settings['receipt-redirect-msg'] ) ) {
				echo '<p>' . esc_html( wptexturize( $this->settings['receipt-redirect-msg'] ) ) . '</p>';
			}
		}

		/**
		 * Trigger actions for 'receipt' page.
		 *
		 * @param int $order_id
		 */
		public function do_receipt_page( $order_id ) {
			$auto_redirect = $this->settings['auto-redirect'] === 'yes';
			if ( ! $auto_redirect ) {
				$this->show_receipt_message();
				$this->logger->log( 'Displaying redirect form for user\'s Order #' . $order_id . '.' );
			} else {
				$this->logger->log( 'Redirecting user\'s Order #' . $order_id . ' to WsPay form.' );
			}

			$wspay_params = $this->wspay->get_wspay_params(
				$this->id,
				$order_id,
				$this->settings['shop-id'],
				$this->settings['secret-key'],
				$this->settings['form-language']
			);
			if ( empty( $wspay_params ) || ! is_array( $wspay_params ) ) {
				$this->logger->log( 'Failed generating WsPay form.', 'critical' );
				return;
			}

			$request_url = $this->wspay->get_request_url( $this->settings['use-wspay-sandbox'] === 'yes' );

			echo $this->get_params_form( $request_url, $wspay_params, ! $auto_redirect );
			if ( $auto_redirect ) {
				$this->enqueue_redirect_js();
			}
		}

		/**
		 * Enqueue JavaScript for redirecting to a WSPay form.
		 */
		private function enqueue_redirect_js() {
			// it's safe to use $ with woocommerce
			// if there's no redirect after 10 seconds, unblock the UI
			wc_enqueue_js( "$('.card').block({message: null, overlayCSS: { background: '#fff', opacity: 0.6 }});" );
			wc_enqueue_js( "setTimeout(function(){ $('.card').unblock(); }, 10000);" );
			wc_enqueue_js( "$('#wcwspay-form').submit();" );
		}

		/**
		 * Convert WSPay parameters to HTML form with inputs representing parameters.
		 *
		 * @param string $request_url
		 * @param array  $wspay_params
		 * @return string
		 */
		private function get_params_form( $request_url, $wspay_params, $show_controls = true ) {
			$form = '<form action="' . esc_attr( $request_url ) . '" method="POST" name="pay" id="wcwspay-form">';
			foreach ( $wspay_params as $key => $param ) {
				$form .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $param ) . '" />';
			}

			if ( $show_controls ) {
				$form .= '<div class="wspay-controls">';
				$form .= '<input class="button button-proceed" type="submit" value="' . __( 'Proceed to WSPay', 'wcwspay' ) . '" />';
				$form .= '<a class="button button-cancel" href="' . $wspay_params['CancelURL'] . '">' . __( 'Cancel order', 'wcwspay' ) . '</a>';
				$form .= '</div>';
			}
			$form .= '</form>';

			return $form;
		}

		/**
		 * Redirect to given URL if possible.
		 *
		 * @codeCoverageIgnore
		 * @param string $url
		 */
		public function call_redirect( $url ) {
			if ( ! headers_sent() ) {
				wp_safe_redirect( $url );
				exit;
			}
		}

		/**
		 * Process response and redirect to a 'thank you' page.
		 */
		public function process_wspay_response() {
			global $woocommerce;

			$response = $_GET;
			$this->logger->log( 'Response received from WSPay.' );
			// does the response contain min of information
			if ( ! isset( $response['Success'] ) || ! isset( $response['ShoppingCartID'] ) ) {
				$this->logger->log( 'WsPay response is invalid.', 'error' );
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Payment unsuccesful', 'wcwspay' ) . '! ' . __( 'Try again or contact site administrator.' ), $notice_type = 'error' );
				}
				return $this->call_redirect( wc_get_checkout_url() );
			}

			$success  = intval( $response['Success'] );
			$order_id = intval( $response['ShoppingCartID'] );
			$order    = wc_get_order( $order_id );
			// is the provided order ID valid?
			if ( ! is_a( $order, 'WC_Order' ) ) {
				$this->logger->log( 'Order #' . $order_id . ' not found', 'critical' );
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Payment unsuccesful', 'wcwspay' ) . '! ' . __( 'Try again or contact site administrator.' ), $notice_type = 'error' );
				}
				return $this->call_redirect( wc_get_checkout_url() );
			}

			if ( ! $this->wspay->is_response_valid( $response, $order ) ) {
				return $this->call_redirect( wc_get_checkout_url() );
			}

			if ( ! isset( $response['ApprovalCode'] ) || ! isset( $response['Signature'] ) ) {
				$this->logger->log( 'ApprovalCode or Signature missing from Order #' . $order->get_order_number() . ' WsPay response.', 'error' );
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Payment unsuccesful', 'wcwspay' ) . '! ' . __( 'Try again or contact site administrator.' ), $notice_type = 'error' );
				}
				return $this->call_redirect( wc_get_checkout_url() );
			}
			// last check, validate signature!
			$approval_code = $response['ApprovalCode'];
			$signature     = $response['Signature'];

			$is_signature_valid = $this->wspay->is_incoming_signature_valid(
				$signature,
				$order_id,
				$this->settings['shop-id'],
				$this->settings['secret-key'],
				$success,
				$approval_code
			);

			if ( $is_signature_valid ) {
				$this->logger->log( 'Payment for Order #' . $order->get_order_number() . ' completed.' );
				$order->add_order_note( __( 'Payment completed via WSPay!', 'wcwspay' ) );
				$order->payment_complete();

				$woocommerce->cart->empty_cart();
				$this->save_response_to_order( $response, $order );

				return $this->call_redirect( $this->get_return_url( $order ) );
			} else {
				$this->logger->log( 'Signatures mismatch for Order #' . $order->get_order_number() . '.', 'critical' );
				$order->add_order_note( __( 'Payment was successful but signatures mismatch was detected. Possible illegal activity!', 'wcwspay' ) );
				return wp_die( 'Possible illegal activity!' );
			}
		}

		/**
		 * Extract various fields from the given response and save them as order meta.
		 *
		 * @param array    $response
		 * @param WC_Order $order
		 */
		private function save_response_to_order( $response, $order ) {
			$empty_check = [ 'DateTime', 'ErrorMessage', 'WsPayOrderId' ];
			foreach ( $this->response_fields as $wc_key => $data ) {
				if ( isset( $response[ $data['wspay_key'] ] ) ) {
					if ( in_array( $data['wspay_key'], $empty_check, true ) ) {
						if ( ! empty( $response[ $data['wspay_key'] ] ) ) {
							$order->add_meta_data( $wc_key, $response[ $data['wspay_key'] ], true );
						}
					} elseif ( $response[ $data['wspay_key'] ] !== '' ) {
						$order->add_meta_data( $wc_key, $response[ $data['wspay_key'] ], true );
					}
				}
			}
			$order->save();
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @override
		 * @param string $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );
			return [
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			];
		}

	}
}
