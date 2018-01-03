<?php

if ( !defined( "ABSPATH" ) ) {
  exit;
}
if ( !class_exists( "WC_Payment_Gateway" ) ) {
  return;
}

/**
 * Register payment gateway's class as a new method of payment.
 * @param array $methods
 * @return array
 */
function wcwspay_add_gateway( $methods ) {
  $methods[] = "WC_WSPay_Payment_Gateway";
  return $methods;
}
add_filter( "woocommerce_payment_gateways", 'wcwspay_add_gateway' );

if ( !class_exists( "WC_WSPay_Payment_Gateway" ) ) {
  class WC_WSPay_Payment_Gateway extends WC_Payment_Gateway {

    private $config = null;

    /**
     * Class constructor with basic gateway's setup
     */
    public function __construct() {
      require_once( dirname( plugin_dir_path(__FILE__) ) . "/utilities/class-wc-wspay-config.php" );
      $this->config = new WC_WSPay_Config();

      $this->id           = "neuralab-wcwspay";
      $this->method_title = __( "WSPay", "wcwspay" );
      $this->has_fields   = true;

      $this->init_form_fields();
      $this->init_settings();

      $this->title = esc_attr( $this->settings["title"] );
      $this->add_actions();
    }

    /**
     * Register different actions
     */
    private function add_actions() {
      add_action( "woocommerce_update_options_payment_gateways_" . $this->id, array( $this, "process_admin_options" ) );
      add_action( "woocommerce_receipt_" . $this->id, array( $this, "do_receipt_page" ) );
      add_action( "woocommerce_thankyou_" . $this->id, array( $this, "show_confirmation_message" ) );
      add_action( "woocommerce_api_" . $this->id, array( $this, "process_wspay_response" ) );
    }

    /**
     * Define gateway's fields visible at WooCommerce's Settings page and
     * Checkout tab.
     * @override
     */
    public function init_form_fields() {
      $this->form_fields = array(
        "enabled" => array(
          "title"     => __( "Enable", "wcwspay" ),
          "type"      => "checkbox",
          "label"     => __( "Enable WSPay Payment Gateway", "wcwspay" ),
          "default"   => "no",
          "desc_tip"  => false
        ),
        "title" => array(
          "title"       => __( "Title", "wcwspay" ),
          "type"        => "text",
          "description" => __( "This controls the title which the user sees during the checkout.", "wcwspay" ),
          "default"     => __( "WSPay", "wcwspay" ),
          "desc_tip"    => true
        ),
        "description-msg" => array(
          "title"       => __( "Description", "wcwspay" ),
          "type"        => "textarea",
          "description" => __( "Payment method description that the customer will see on your checkout.", "wcwspay" ),
          "default"     => "",
          "desc_tip"    => true
        ),
        "confirmation-msg" => array(
          "title"       => __( "Confirmation", "wcwspay" ),
          "type"        => "textarea",
          "description" => __( "Confirmation message that will be added to the 'thank you' page.", "wcwspay" ),
          "default"     => __( "Your account has been charged and your transaction is successful.", "wcwspay" ),
          "desc_tip"    => true
        ),
        "receipt-redirect-msg" => array(
          "title"       => __( "Receipt", "wcwspay" ),
          "type"        => "textarea",
          "description" => __( "Message that will be added to the 'receipt' page. Shown if automatic redirect is enabled.", "wcwspay" ),
          "default"     => __( "Please click on the button below.", "wcwspay" ),
          "desc_tip"    => true
        ),
        "shop-id" => array(
          "title"       => __( "Shop ID", "wcwspay" ),
          "type"        => "text",
          "description" => __( "Web shop's unique identification string.", "wcwspay" ),
          "default"     => "",
          "desc_tip"    => true
        ),
        "secret-key" => array(
          "title"       => __( "Secret Key", "wcwspay" ),
          "type"        => "password",
          "description" => __( "Secret key for signing orders.", "wcwspay" ),
          "default"     => "",
          "desc_tip"    => true
        ),
        "form-language" => array(
          "title"       => __( "Form Language", "wcwspay" ),
          "type"        => "select",
          "description" => __( "Language of the WSPay form.", "wcwspay" ),
          "default"     => "EN",
          "desc_tip"    => true,
          "options"     => array(
            "HR"  => __( "Croatian", "wcwspay" ),
            "CZ"  => __( "Czech", "wcwspay" ),
            "NL"  => __( "Dutch", "wcwspay" ),
            "EN"  => __( "English", "wcwspay" ),
            "FR"  => __( "French", "wcwspay" ),
            "DE"  => __( "German", "wcwspay" ),
            "HU"  => __( "Hungarian", "wcwspay" ),
            "IT"  => __( "Italian", "wcwspay" ),
            "PL"  => __( "Polish", "wcwspay" ),
            "PT"  => __( "Portuguese", "wcwspay" ),
            "RU"  => __( "Russian", "wcwspay" ),
            "SK"  => __( "Slovak", "wcwspay" ),
            "SL"  => __( "Slovenian", "wcwspay" ),
            "ES"  => __( "Spanish", "wcwspay" ),
          )
        ),
        "auto-redirect" => array(
          "title"       => __( "Automatic redirect", "wcwspay" ),
          "type"        => "checkbox",
          "label"       => __( "Enable automatic redirect to the WSPay Form.", "wcwspay" ),
          "description" => __( "This option is using JavaScript (Ajax).", "wcwspay" ),
          "default"     => "yes",
          "desc_tip"    => true
        ),
        "use-wspay-sandbox" => array(
          "title"       => __( "WSPay Sandbox", "wcwspay" ),
          "type"        => "checkbox",
          "label"       => __( "Enable WSPay Sandbox.", "wcwspay" ),
          "description" => __( "Sandbox is used for testing purposes, disable this for live web shops.", "wcwspay" ),
          "default"     => "no",
          "desc_tip"    => true
        )
      );
    }

    /**
     * Echoes gateway's options (Checkout tab under WooCommerce's settings)
     * @override
     */
    public function admin_options() {
      echo "<h2>" . __( "Neuralab's WSPay Payment Gateway", "wcwspay" ) . "</h2>";
      echo "<table class='form-table'>";
      $this->generate_settings_html();
      echo "</table>";
    }

    /**
     * Display description of the gateway on the checkout page
     * @override
     */
    public function payment_fields() {
      if ( isset($this->settings["description-msg"]) && !empty($this->settings["description-msg"]) ) {
        echo wptexturize( $this->settings["description-msg"] );
      }
    }

    /**
     * Echo confirmation message on the 'thank you' page
     */
    public function show_confirmation_message() {
      if ( isset($this->settings["confirmation-msg"]) && !empty($this->settings["confirmation-msg"]) ) {
        echo "<p>" . wptexturize( $this->settings["confirmation-msg"] ) . "</p>";
      }
    }

    /**
     * Echo redirect message on the 'receipt' page
     */
    private function show_receipt_message() {
      if ( isset($this->settings["receipt-redirect-msg"]) && !empty($this->settings["receipt-redirect-msg"]) ) {
        echo "<p>" . wptexturize( $this->settings["receipt-redirect-msg"] ) . "</p>";
      }
    }

    /**
     * Trigger actions for 'receipt' page
     * @param int $order_id
     */
    public function do_receipt_page( $order_id ) {
      $auto_redirect = $this->settings["auto-redirect"] === "yes";
      if ( !$auto_redirect ) {
        $this->show_receipt_message();
      }

      $wspay_params = $this->get_wspay_params( $order_id );
      $request_url  = $this->get_request_url();
      if ( empty($request_url) || !is_string($request_url) ) {
        return;
      }
      echo $this->get_params_form( $request_url, $wspay_params, !$auto_redirect );
      if ( $auto_redirect ) {
        $this->enqueue_redirect_js();
      }
    }

    /**
     * Return testing or production request URL
     * @return string
     */
    private function get_request_url() {
      if ( $this->settings["use-wspay-sandbox"] === "yes" ) {
        return $this->config->get( "test_request_url" );
      }
<<<<<<< HEAD
=======

>>>>>>> 12598e0101f4768512a385674293da5ecd4e4222
      return $this->config->get( "production_request_url" );
    }

    /**
     * Return order's total amount in right format for WSPay
     * @param WC_Order $order
     * @return string
     */
    private function process_order_total( $order ) {
      $order_total = explode( ".", $order->get_total() );

      if( empty( $order_total[1] ) ) {
        return $order_total[0] . ",00";
      } else {
        return $order_total[0] . "," . $order_total[1];
      }
    }

    /**
     * Generate and return md5 encrypted signature.
     * Signature is generated from the following string:
     * shop_id + secret_key + order_id + secret_key + order_total + secret_key
     * @param int    $order_id
     * @param string $order_total
     * @param string $shop_id
     * @param string $secret_key
     * @return mixed - encrypted string or false in case of failure
     */
    private function get_signature( $order_id, $order_total, $shop_id, $secret_key ) {
      // total amount must be striped from any dots and/or commas
      $order_total = str_replace( [",", "."], "", $order_total );

      if ( empty($shop_id) || empty($secret_key) || empty($order_id) || empty($order_total) ) {
        return false;
      }

      return md5( $shop_id . $secret_key . $order_id . $secret_key . $order_total . $secret_key );
    }

    /**
     * Compares the given incoming signature with the one generated from the
     * rest of the parameters. Returns true if they match or false otherwise.
     * @param string $incoming_signature
     * @param int    $order_id
     * @param string $shop_id
     * @param string $secret_key
     * @param int    $success
     * @param string $approval_code
     * @return boolean
     */
    private function is_incoming_signature_valid( $incoming_signature, $order_id, $shop_id, $secret_key, $success, $approval_code ) {
      $new_signature = md5( $shop_id . $secret_key . $order_id . $secret_key . $success . $secret_key . $approval_code . $secret_key );
      if ( $incoming_signature === $new_signature ) {
        return true;
      }
      return false;
    }

    /**
     * Enqueue JavaScript for redirecting to a WSPay form
     */
    private function enqueue_redirect_js() {
      // it's safe to use $ with woocommerce
      // if there's no redirect after 10 seconds, unblock the UI
      wc_enqueue_js("$('.card').block({message: null, overlayCSS: { background: '#fff', opacity: 0.6 }});");
      wc_enqueue_js("setTimeout(function(){ $('.card').unblock(); }, 10000)");
      wc_enqueue_js("$('#wcwspay-form').submit();");
    }

    /**
     * Convert WSPay parameters to HTML form with inputs representing parameters
     * @param string $request_url
     * @param array  $wspay_params
     * @return string
     */
    private function get_params_form( $request_url, $wspay_params, $show_controls = true ) {
      $form = "<form action='" . esc_attr( $request_url ) . "' method='POST' name='pay' id='wcwspay-form'>";
      foreach( $wspay_params as $key => $param ) {
        $form .= "<input type='hidden' name='" . esc_attr( $key ) . "' value='" . esc_attr( $param ) . "' />";
      }

      if ( $show_controls ) {
        $form .= "<div class='wspay-controls'>";
        $form .= "<input class='button button-proceed' type='submit' value='" . __( "Proceed to WSPay", "wcwspay" ) . "' />";
        $form .= "<a class='button button-cancel' href='" . $wspay_params["CancelURL"] . "'>" . __( "Cancel order", "wcwspay" ) . "</a>";
        $form .= "</div>";
      }
      $form .= "</form>";

      return $form;
    }

    /**
     * Generate WSPay WSPay form and display it to an user
     * @param int $order_id
     */
    private function get_wspay_params( $order_id ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );
      $return_url  = $woocommerce->api_request_url( $this->id );
      $order_total = $this->process_order_total( $order );
      $shop_id     = $this->settings["shop-id"];
      $secret_key  = $this->settings["secret-key"];
      $form_lang   = $this->settings["form-language"];
      $signature   = $this->get_signature( $order_id, $order_total, $shop_id, $secret_key );
      if ( empty($signature) || !is_string($signature) ) {
        return;
      }

      $wspay_params = array(
<<<<<<< HEAD
        "ShopID"            => $shop_id,
        "ShoppingCartID"    => $order_id,
        "TotalAmount"       => $order_total,
        "Signature"         => $signature,
        "ReturnURL"         => $return_url,
        "ReturnErrorURL"    => $return_url,
        "CancelURL"         => $order->get_cancel_order_url_raw(),
        // optionals parameters:
        "Lang"              => $form_lang,
        "CustomerFirstName" => $order->get_billing_first_name(),
        "CustomerLastName"  => $order->get_billing_last_name(),
        "CustomerAddress"   => $order->get_billing_address_1(),
        "CustomerCity"      => $order->get_billing_city(),
        "CustomerZIP"       => $order->get_billing_postcode(),
        "CustomerCountry"   => $order->get_billing_country(),
        "CustomerEmail"     => $order->get_billing_email(),
        "CustomerPhone"     => $order->get_billing_phone()
=======
        "ShopID"              => $shop_id,
        "ShoppingCartID"      => $order_id,
        "TotalAmount"         => $order_total,
        "Signature"           => $signature,
        "ReturnURL"           => $return_url,
        "ReturnErrorURL"      => $return_url,
        "CancelURL"           => $order->get_cancel_order_url_raw(),
        // optionals parameters:
        "Lang"                => $form_lang,
        "CustomerFirstName"   => $order->get_billing_first_name(),
        "CustomerLastName"    => $order->get_billing_last_name(),
        "CustomerAddress"     => $order->get_billing_address_1(),
        "CustomerCity"        => $order->get_billing_city(),
        "CustomerZIP"         => $order->get_billing_postcode(),
        "CustomerCountry"     => $order->get_billing_country(),
        "CustomerEmail"       => $order->get_billing_email(),
        "CustomerPhone"       => $order->get_billing_phone()
>>>>>>> 12598e0101f4768512a385674293da5ecd4e4222
      );

      if ( !empty($order->get_billing_address_2()) ) {
         $wspay_params["CustomerAddress"] .= ", " . $order->get_billing_address_2();
      }

      return $wspay_params;
    }

    /**
     * Check response for any error messages or invalid status. Return false
     * in case of error.
     * @param array    $response
     * @param WC_Order $order
     * @return boolean
     */
    private function is_wspay_response_valid( $response, $order ) {
      if ( intval( $response["Success"] ) !== 1 ) {
        $order->update_status( "pending", __( "Payment unsuccesful", "wcwspay" ) );

        if( function_exists( "wc_add_notice" ) ) {
          wc_add_notice( __( "Payment unsuccesful", "wcwspay" ) . "! " . __( "Try again or contact site administrator."), $notice_type = "error" );
        }
        return false;

      } else if ( $response["ErrorMessage"] === "ODBIJENO" ) {
        $order->update_status( "pending", __( "Payment denied", "wcwspay" ) );

        if( function_exists( "wc_add_notice" ) ) {
          wc_add_notice( __( "Payment for order " . $order->get_order_number() . " rejected!", "wcwspay" ) . " " . __( "Please contact site administrator.", "wcwspay"), $notice_type = "error" );
        }
        return false;
      }

      return true;
    }

    /**
     * Process response and redirect to a 'thank you' page.
     */
    public function process_wspay_response() {
      global $woocommerce;

      $response = $_GET;
      $success  = intval( $response["Success"] );
      $order_id = intval( $response["ShoppingCartID"] );
      $order    = new WC_Order( $order_id );

      if ( !$this->is_wspay_response_valid( $response, $order ) ) {
        wp_redirect( wc_get_checkout_url() );
        exit;
      }

      $approval_code = $response["ApprovalCode"];
      $signature     = $response["Signature"];

      $is_signature_valid = $this->is_incoming_signature_valid( $signature, $order_id, $this->settings["shop-id"], $this->settings["secret-key"], $success, $approval_code );
      if ( $is_signature_valid ) {
        $order->add_order_note( __( "Payment completed via WSPay!", "wcwspay" ) );
        $order->payment_complete();

        wc_reduce_stock_levels( $order->get_id() );
        $woocommerce->cart->empty_cart();

        wp_redirect( $this->get_return_url($order) );
        exit;
      } else {
        $order->add_order_note( __( "Payment was successful but signatures mismatch was detected. Possible illegal activity!", "wcwspay" ) );
        wp_die( "Possible illegal activity!" );
      }

    }

    /**
     * Process the payment and return the result
     * @override
     * @param string $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
      $order = new WC_Order( $order_id );
      return array(
        "result"    => "success",
        "redirect"  => $order->get_checkout_payment_url( true )
      );
    }

  }
}
