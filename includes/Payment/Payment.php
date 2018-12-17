<?php
/**
 * @file Contains Payment class.
 */

namespace IDPay\CF7\Payment;

use IDPay\CF7\ServiceInterface;

/**
 * Class Payment
 *
 * This class defines a method which will be hooked into an event when
 * a contact form is going to be submitted.
 * In that method we want to redirect to IDPay payment gateway if everything is
 * ok.
 *
 * @package IDPay\CF7\Payment
 */
class Payment implements ServiceInterface {

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'wpcf7_mail_sent', array( $this, 'after_send_mail' ) );
	}

	/** Hooks into 'wpcf7_mail_sent'.
	 *
	 * @param $cf7
	 *   the contact form's data which is submitted.
	 */
	public function after_send_mail( $cf7 ) {

		global $postid;
		$postid = $cf7->id();

		$enable = get_post_meta( $postid, "_idpay_cf7_enable", TRUE );

		if ( $enable == "1" ) {

			global $wpdb;
			global $postid;

			$wpcf7       = \WPCF7_ContactForm::get_current();
			$submission  = \WPCF7_Submission::get_instance();
			$phone       = '';
			$description = '';
			$amount      = '';
			$email       = '';

			if ( $submission ) {
				$data        = $submission->get_posted_data();
				$phone       = isset( $data['idpay_phone'] ) ? $data['idpay_phone'] : "";
				$description = isset( $data['idpay_description'] ) ? $data['idpay_description'] : "";
				$amount      = isset( $data['idpay_amount'] ) ? $data['idpay_amount'] : "";
				$email       = isset( $data['your-email'] ) ? $data['your-email'] : "";
			}

			$predefined_amount = get_post_meta( $postid, "_idpay_cf7_amount", TRUE );

			if ( $predefined_amount !== "" ) {
				$amount = $predefined_amount;
			}
			$options = get_option( 'idpay_cf7_options' );
			foreach ( $options as $k => $v ) {
				$value[ $k ] = $v;
			}
			$active_gateway = 'IDPay';
			$url_return     = $value['return'];
			$table_name     = $wpdb->prefix . "cf7_transactions";

			$row                = array();
			$row['form_id']     = $postid;
			$row['trans_id']    = '';
			$row['gateway']     = $active_gateway;
			$row['amount']      = $amount;
			$row['created_at']  = time();
			$row['phone']       = $phone;
			$row['description'] = $description;
			$row['email']       = $email;
			$row['status']      = 'pending';
			$row_format         = array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			);

			if ( $active_gateway == 'IDPay' ) {

				$api_key = $value['api_key'];
				$sandbox = $value['sandbox'] == 1 ? 'true' : 'false';
				$amount  = intval( $amount );

				$desc = $description;

				if ( empty( $amount ) ) {
					$tmp = 'واحد پول انتخاب شده پشتیبانی نمی شود.' . ' لطفا به مدیر اطلاع دهید <br>';
					$tmp .= '<a href="' . get_option( 'siteurl' ) . '" class="mrbtn_red" > بازگشت به سایت </a>';
					echo '<html>
                            <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                            <title>خطا در عملیات پرداخت</title>
                            </head>
                            <link rel="stylesheet"  media="all" type="text/css" href="' . plugins_url( 'style.css', __FILE__ ) . '">
                            <body>	
                            <div> 
                            <h3><span>خطا در عملیات پرداخت</span></h3>
                            ' . $tmp . '	
                            </div>
                            </body>
                            </html>';
				}

				$data = array(
					'order_id' => time(),
					'amount'   => $amount,
					'phone'    => $phone,
					'desc'     => $desc,
					'callback' => $url_return,
				);

				$ch = curl_init( 'https://api.idpay.ir/v1/payment' );
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

				error_log( print_r( $result, TRUE ) );

				if ( $http_status != 201 || empty( $result ) || empty( $result->id ) || empty( $result->link ) ) {
					$tmp = sprintf( 'خطا هنگام ایجاد تراکنش. وضعیت خطا: %s - کد خطا: %s - پیام خطا: %s', $http_status, $result->error_code, $result->error_message ) . '<br> لطفا به مدیر اطلاع دهید <br><br>';
					$tmp .= '<a href="' . get_option( 'siteurl' ) . '" class="mrbtn_red" > بازگشت به سایت </a>';
					echo '<html>
                            <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                            <title>خطا در عملیات پرداخت</title>
                            </head>
                            <link rel="stylesheet"  media="all" type="text/css" href="' . plugins_url( 'style.css', __FILE__ ) . '">
                            <body>	
                            <div> 
                            <h3><span>خطا در عملیات پرداخت</span></h3>
                                                ' . $tmp . '	
                            </div>
                            </body>
                            </html>';
				} else {
					$row['trans_id'] = $result->id;
					$wpdb->insert( $table_name, $row, $row_format );
					Header( 'Location: ' . $result->link );
				}
				exit();
			}

		}
	}
}