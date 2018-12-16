<?php
namespace IDPay\CF7\Payment;
use IDPay\CF7\ServiceInterface;

class Callback implements ServiceInterface {
	public function register() {
		add_shortcode( 'idpay_cf7_result', array( $this, 'handler' ) );
	}

	public function handler( $atts ) {
		global $wpdb;
		$options = get_option( 'idpay_cf7_options' );
		foreach ( $options as $k => $v ) {
			$value[ $k ] = $v;
		}

		if ( ! empty( $_POST['id'] ) && ! empty( $_POST['order_id'] ) ) {
			$pid       = $_POST['id'];
			$porder_id = $_POST['order_id'];

			$cf_Form = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "cf7_transactions WHERE transid='$pid'" );
			if ( $cf_Form !== NULL ) {
				$price = $cf_Form->cost;
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
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array( 'status' => 'error' ), array( 'transid' => $pid ), array( '%s' ), array( '%d' ) );

				return '<b style="color:#f44336;">' . sprintf( 'خطا هنگام بررسی وضعیت تراکنش. کد خطا: %s', $http_status ) . '<b/>';;
			}

			$inquiry_status   = empty( $result->status ) ? NULL : $result->status;
			$inquiry_track_id = empty( $result->track_id ) ? NULL : $result->track_id;
			$inquiry_order_id = empty( $result->order_id ) ? NULL : $result->order_id;
			$inquiry_amount   = empty( $result->amount ) ? NULL : $result->amount;

			if ( empty( $inquiry_status ) || empty( $inquiry_track_id ) || empty( $inquiry_amount ) || $inquiry_amount != $price || $inquiry_status != 100 ) {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array( 'status' => 'error' ), array( 'transid' => $pid ), array( '%s' ), array( '%d' ) );

				return '<b style="color:#f44336;">' . $this->failed_message( $value['failed_massage'], $inquiry_track_id, $inquiry_order_id ) . '<b/>';
			} else {
				$wpdb->update( $wpdb->prefix . 'cf7_transactions', array(
					'status'  => 'success',
					'transid' => $inquiry_track_id,
				), array( 'transid' => $pid ), array( '%s', '%s' ), array( '%d' ) );

				return '<b style="color:#8BC34A;">' . $this->success_message( $value['success_massage'], $inquiry_track_id, $inquiry_order_id ) . '<b/>';
			}
		} else {
			return $body = '<b style="color:#f44336;">تراکنش یافت نشد<b/>';
		}
	}


	function failed_message( $failed_massage, $track_id, $order_id ) {
		return str_replace( [ "{track_id}", "{order_id}" ], [
			$track_id,
			$order_id,
		], $failed_massage );
	}

	function success_message( $success_massage, $track_id, $order_id ) {
		return str_replace( [ "{track_id}", "{order_id}" ], [
			$track_id,
			$order_id,
		], $success_massage );
	}

}