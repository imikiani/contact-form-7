<?php
/**
 * @file Conatins Callback class.
 */

namespace IDPay\CF7\Payment;

use IDPay\CF7\ServiceInterface;

/**
 * Class Callback
 *
 * Handles reacting on definition of a short code.
 *
 * The short code should be inserted into a page so that a
 * coming transaction can be verified.
 *
 * @package IDPay\CF7\Payment
 */
class Callback implements ServiceInterface {

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_shortcode( 'idpay_cf7_result', array( $this, 'handler' ) );
	}

	/**
	 * Reacts on definition of short code 'idpay_cf7_result', whenever it is
	 * defined.
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function handler( $atts ) {

		$status   = empty( $_POST['status'] ) ? NULL : $_POST['status'];
		$track_id = empty( $_POST['track_id'] ) ? NULL : $_POST['track_id'];
		$id       = empty( $_POST['id'] ) ? NULL : $_POST['id'];
		$order_id = empty( $_POST['order_id'] ) ? NULL : $_POST['order_id'];
		$amount   = empty( $_POST['amount'] ) ? NULL : $_POST['amount'];

		global $wpdb;
		$value   = array();
		$options = get_option( 'idpay_cf7_options' );
		foreach ( $options as $k => $v ) {
			$value[ $k ] = $v;
		}

		if ( ! empty( $id ) && ! empty( $order_id ) ) {

			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "cf7_transactions WHERE trans_id='%s'", $id ) );
			if ( $row !== NULL ) {
				if ( $row->status == 'completed' ) {
					return '<b style="color:#8BC34A;">' . $this->success_message( $value['success_message'], $row->track_id, $row->order_id ) . '</b>';
				}
			}

			if ( $status != 10 ) {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array(
					'status'   => 'failed',
					'track_id' => $track_id,
				), array( 'trans_id' => $id ), array(
					'%s',
					'%s',
				), array( '%d' ) );

				return '<b style="color:#f44336;">' . $this->failed_message( $value['failed_message'], $track_id, $order_id ) . '</b>';
			}

			$api_key = $value['api_key'];
			$sandbox = $value['sandbox'] == 1 ? 'true' : 'false';

			$data = array(
				'id'       => $id,
				'order_id' => $order_id,
			);


			$headers = array(
				'Content-Type' => 'application/json',
				'X-API-KEY'    => $api_key,
				'X-SANDBOX'    => $sandbox,
			);
			$args    = array(
				'body'    => json_encode( $data ),
				'headers' => $headers,
				'timeout' => 15,
			);

			$response = $this->call_gateway_endpoint( 'https://api.idpay.ir/v1.1/payment/verify', $args );

			if ( is_wp_error( $response ) ) {
				return '<b style="color:#f44336;">' . $response->get_error_message() . '</b>';
			}

			$http_status = wp_remote_retrieve_response_code( $response );
			$result      = wp_remote_retrieve_body( $response );
			$result      = json_decode( $result );

			if ( $http_status != 200 ) {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array( 'status' => 'failed' ), array( 'trans_id' => $id ), array( '%s' ), array( '%d' ) );

				return '<b style="color:#f44336;">' . sprintf( __( 'An error occurred while verifying a transaction. error status: %s, error code: %s, error message: %s', 'idpay-contact-form-7' ), $http_status, $result->error_code, $result->error_message ) . '</b>';
			}

			$verify_status   = empty( $result->status ) ? NULL : $result->status;
			$verify_track_id = empty( $result->track_id ) ? NULL : $result->track_id;
			$verify_id       = empty( $result->id ) ? NULL : $result->id;
			$verify_order_id = empty( $result->order_id ) ? NULL : $result->order_id;
			$verify_amount   = empty( $result->amount ) ? NULL : $result->amount;

			if ( empty( $verify_status ) || empty( $verify_track_id ) || empty( $verify_amount ) || $verify_amount != $amount || $verify_status < 100 ) {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array(
					'status'   => 'failed',
					'track_id' => $verify_track_id,
				), array( 'trans_id' => $verify_id ), array(
					'%s',
					'%s',
				), array( '%d' ) );

				return '<b style="color:#f44336;">' . $this->failed_message( $value['failed_message'], $verify_track_id, $verify_order_id ) . '</b>';
			} else {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array(
					'status'   => 'completed',
					'track_id' => $verify_track_id,
				), array( 'trans_id' => $verify_id ), array(
					'%s',
					'%s',
				), array( '%d' ) );

				return '<b style="color:#8BC34A;">' . $this->success_message( $value['success_message'], $verify_track_id, $verify_order_id ) . '</b>';
			}
		} else {
			return '<b style="color:#f44336;">' . __( 'Transaction not found', 'idpay-contact-form-7' ) . '</b>';
		}
	}

	/**
	 * Shows a configured message when a payment is successful.
	 * This message can be configured at the Wordpress dashboard.
	 * Also note that the message will be shown
	 * if the short code has been inserted in a page.
	 *
	 * @see \IDPay\CF7\Admin\Menu::admin_table()
	 *
	 * @param $failed_message
	 * @param $track_id
	 * @param $order_id
	 *
	 * @return string
	 */
	private function failed_message( $failed_message, $track_id, $order_id ) {
		return str_replace( [ "{track_id}", "{order_id}" ], [
			$track_id,
			$order_id,
		], $failed_message );
	}

	/**
	 * Show a configured message when a payment is unsuccessful.
	 * This message can be configured at the Wordpress dashboard.
	 * Also note that the message will be shown
	 * if the short code has been inserted in a page.
	 *
	 * @see \IDPay\CF7\Admin\Menu::admin_table()
	 *
	 * @param $success_message
	 * @param $track_id
	 * @param $order_id
	 *
	 * @return mixed.
	 */
	private function success_message( $success_message, $track_id, $order_id ) {
		return str_replace( [ "{track_id}", "{order_id}" ], [
			$track_id,
			$order_id,
		], $success_message );
	}

	/**
	 * Calls the gateway endpoints.
	 *
	 * Tries to get response from the gateway for 4 times.
	 *
	 * @param $url
	 * @param $args
	 *
	 * @return array|\WP_Error
	 */
	private function call_gateway_endpoint( $url, $args ) {
		$number_of_connection_tries = 4;
		while ( $number_of_connection_tries ) {
			$response = wp_safe_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$number_of_connection_tries --;
				continue;
			} else {
				break;
			}
		}

		return $response;
	}

}