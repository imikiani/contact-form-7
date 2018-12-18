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
		global $wpdb;
		$options = get_option( 'idpay_cf7_options' );
		foreach ( $options as $k => $v ) {
			$value[ $k ] = $v;
		}

		if ( ! empty( $_POST['id'] ) && ! empty( $_POST['order_id'] ) ) {
			$pid       = $_POST['id'];
			$porder_id = $_POST['order_id'];

			$cf_Form = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "cf7_transactions WHERE trans_id='$pid'" );
			if ( $cf_Form !== NULL ) {
				$amount = $cf_Form->amount;
			}

			$api_key = $value['api_key'];
			$sandbox = $value['sandbox'] == 1 ? 'true' : 'false';

			$data = array(
				'id'       => $pid,
				'order_id' => $porder_id,
			);

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, 'https://api.idpay.ir/v1/payment/inquiry' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'X-API-KEY:' . $api_key,
				'X-SANDBOX:' . $sandbox,
			) );

			$result      = curl_exec( $ch );
			$result      = json_decode( $result );
			$http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			curl_close( $ch );

			if ( $http_status != 200 ) {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array( 'status' => 'failed' ), array( 'trans_id' => $pid ), array( '%s' ), array( '%d' ) );

				return '<b style="color:#f44336;">' . sprintf( 'خطا هنگام بررسی وضعیت تراکنش. وضعیت خطا: %s - کد خطا: %s - پیام خطا: %s', $http_status, $result->error_code, $result->error_message ) . '<b/>';;
			}

			$inquiry_status   = empty( $result->status ) ? NULL : $result->status;
			$inquiry_track_id = empty( $result->track_id ) ? NULL : $result->track_id;
			$inquiry_order_id = empty( $result->order_id ) ? NULL : $result->order_id;
			$inquiry_amount   = empty( $result->amount ) ? NULL : $result->amount;

			if ( empty( $inquiry_status ) || empty( $inquiry_track_id ) || empty( $inquiry_amount ) || $inquiry_amount != $amount || $inquiry_status != 100 ) {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array(
					'status'   => 'failed',
					'track_id' => $inquiry_track_id,
				), array( 'trans_id' => $pid ), array(
					'%s',
					'%s',
				), array( '%d' ) );

				return '<b style="color:#f44336;">' . $this->failed_message( $value['failed_message'], $inquiry_track_id, $inquiry_order_id ) . '<b/>';
			} else {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array(
					'status'   => 'completed',
					'track_id' => $inquiry_track_id,
				), array( 'trans_id' => $pid ), array(
					'%s',
					'%s',
				), array( '%d' ) );

				return '<b style="color:#8BC34A;">' . $this->success_message( $value['success_message'], $inquiry_track_id, $inquiry_order_id ) . '<b/>';
			}
		} else {
			return $body = '<b style="color:#f44336;">تراکنش یافت نشد<b/>';
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
	function failed_message( $failed_message, $track_id, $order_id ) {
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
	function success_message( $success_message, $track_id, $order_id ) {
		return str_replace( [ "{track_id}", "{order_id}" ], [
			$track_id,
			$order_id,
		], $success_message );
	}

}