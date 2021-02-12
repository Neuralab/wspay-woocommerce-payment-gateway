<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WC_WSPay_Config' ) ) {
	class WC_WSPay_Config {

		private $data = null;

		/**
		 * Trys to load data with the default file name
		 */
		public function __construct() {
			$this->load_from_file();
		}

		/**
		 * Loads data from file which should be JSON text file in root directory.
		 * Returns true if file is successfully loaded.
		 *
		 * @param string $file_name
		 * @return boolean
		 */
		public function load_from_file( $file_name = 'config.json' ) {
			$current_dir = dirname( plugin_dir_path( __FILE__ ) );
			try {
				$this->data = json_decode( file_get_contents( dirname( $current_dir ) . '/' . $file_name ) );
				return true;
			} catch ( Exception $ex ) {
				$this->data = null;
			}

			return false;
		}

		/**
		 * Returns the value of config field if exists or false if it doesn't.
		 *
		 * @param string $field_name
		 * @return string|boolean value of config field or false
		 */
		public function get( $field_name ) {
			if ( empty( $this->data ) && gettype( $this->data ) !== 'object' ) {
				return false;
			}
			if ( property_exists( $this->data, $field_name ) ) {
				return $this->data->{ $field_name };
			}
			return false;
		}

	}
}
