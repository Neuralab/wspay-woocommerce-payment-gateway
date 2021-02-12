<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


return [
	'enabled' => [
		'title'     => __( 'Enable', 'wcwspay' ),
		'type'      => 'checkbox',
		'label'     => __( 'Enable WSPay Payment Gateway', 'wcwspay' ),
		'default'   => 'no',
		'desc_tip'  => false
	],
	'title' => [
		'title'       => __( 'Title', 'wcwspay' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during the checkout.', 'wcwspay' ),
		'default'     => __( 'WSPay', 'wcwspay' ),
		'desc_tip'    => true
	],
	'description-msg' => [
		'title'       => __( 'Description', 'wcwspay' ),
		'type'        => 'textarea',
		'description' => __( 'Payment method description that the customer will see on your checkout.', 'wcwspay' ),
		'default'     => '',
		'desc_tip'    => true
	],
	'confirmation-msg' => [
		'title'       => __( 'Confirmation', 'wcwspay' ),
		'type'        => 'textarea',
		'description' => __( 'Confirmation message that will be added to the "thank you" page.', 'wcwspay' ),
		'default'     => __( 'Your account has been charged and your transaction is successful.', 'wcwspay' ),
		'desc_tip'    => true
	],
	'receipt-redirect-msg' => [
		'title'       => __( 'Receipt', 'wcwspay' ),
		'type'        => 'textarea',
		'description' => __( 'Message that will be added to the "receipt" page. Shown if automatic redirect is enabled.', 'wcwspay' ),
		'default'     => __( 'Please click on the button below.', 'wcwspay' ),
		'desc_tip'    => true
	],
	'shop-id' => [
		'title'       => __( 'Shop ID', 'wcwspay' ),
		'type'        => 'text',
		'description' => __( 'Web shop\'s unique identification string.', 'wcwspay' ),
		'default'     => '',
		'desc_tip'    => true
	],
	'secret-key' => [
		'title'       => __( 'Secret Key', 'wcwspay' ),
		'type'        => 'password',
		'description' => __( 'Secret key for signing orders.', 'wcwspay' ),
		'default'     => '',
		'desc_tip'    => true
	],
	'form-language' => [
		'title'       => __( 'Form Language', 'wcwspay' ),
		'type'        => 'select',
		'description' => __( 'Language of the WSPay form.', 'wcwspay' ),
		'default'     => 'EN',
		'desc_tip'    => true,
		'options'     => [
			'HR'  => __( 'Croatian', 'wcwspay' ),
			'CZ'  => __( 'Czech', 'wcwspay' ),
			'NL'  => __( 'Dutch', 'wcwspay' ),
			'EN'  => __( 'English', 'wcwspay' ),
			'FR'  => __( 'French', 'wcwspay' ),
			'DE'  => __( 'German', 'wcwspay' ),
			'HU'  => __( 'Hungarian', 'wcwspay' ),
			'IT'  => __( 'Italian', 'wcwspay' ),
			'PL'  => __( 'Polish', 'wcwspay' ),
			'PT'  => __( 'Portuguese', 'wcwspay' ),
			'RU'  => __( 'Russian', 'wcwspay' ),
			'SK'  => __( 'Slovak', 'wcwspay' ),
			'SL'  => __( 'Slovenian', 'wcwspay' ),
			'ES'  => __( 'Spanish', 'wcwspay' ),
		]
	],
	'auto-redirect' => [
		'title'       => __( 'Automatic redirect', 'wcwspay' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable automatic redirect to the WSPay Form.', 'wcwspay' ),
		'description' => __( 'This option is using JavaScript (Ajax).', 'wcwspay' ),
		'default'     => 'yes',
		'desc_tip'    => true
	],
	'use-wspay-sandbox' => [
		'title'       => __( 'WSPay Sandbox', 'wcwspay' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable WSPay Sandbox.', 'wcwspay' ),
		'description' => __( 'Sandbox is used for testing purposes, disable this for live web shops.', 'wcwspay' ),
		'default'     => 'no',
		'desc_tip'    => true
	],
	'use-logger' => [
		'title'       => __( 'Debug log', 'wcwspay' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'wcwspay' ),
		'default'     => 'no',
		'description' => sprintf( __( 'Log gateway events, stored in %s', 'wcwspay' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'wcwspay' ) . '</code>' )
	],
	'use-mailer' => [
		'title'       => __( 'Log mailer', 'wcwspay' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable log mailer', 'wcwspay' ),
		'default'     => 'no',
		'description' => __( 'Sends log messages to defined e-mail address for defined minimum log level.', 'wcwspay' ),
	],
	'mailer-address' => array(
		'type'        => 'text',
		'label'       => __( 'Mailer address', 'wcwspay' ),
		'default'     => get_bloginfo( 'admin_email' ),
		'desc_tip'    => true,
		'description' => __( 'Mailer\'s address. Defaults to admin email (set in Settings > General).', 'wcwspay' ),
	),
	'mailer-min-log-level' => [
		'type'        => 'select',
		'label'       => __( 'Mailer minimum log level', 'wcwspay' ),
		'default'     => 'error',
		'desc_tip'    => true,
		'description' => __( 'Sorted by urgency. E.g. if "Critical" is selected, mails will also be sent for "Alert" and "Emergency" log messages.', 'wcwspay' ),
		'options'     => [
			'emergency' => __( 'Emergency', 'wcwspay' ),
			'alert'     => __( 'Alert', 'wcwspay' ),
			'critical'  => __( 'Critical', 'wcwspay' ),
			'error'     => __( 'Error', 'wcwspay' ),
			'warning'   => __( 'Warning', 'wcwspay' ),
			'notice'    => __( 'Notice', 'wcwspay' ),
			'info'      => __( 'Info', 'wcwspay' ),
			'debug'     => __( 'Debug', 'wcwspay' ),
		]
	],
];
