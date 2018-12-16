<?php

namespace IDPay\CF7;

class Payment implements ServiceInterface {

	public function register() {
		add_action( 'wpcf7_mail_sent', array( $this, 'after_send_mail' ) );
	}
	public function after_send_mail( $cf7 ) {

		global $postid;
		$postid = $cf7->id();

		$enable = get_post_meta( $postid, "_idpay_cf7_enable", TRUE );
		$email  = get_post_meta( $postid, "_idpay_cf7_email", TRUE );

		if ( $enable == "1" ) {
			if ( $email == "2" ) {
				global $wpdb;
				global $postid;

				$wpcf7       = \WPCF7_ContactForm::get_current();
				$submission  = \WPCF7_Submission::get_instance();
				$user_email  = '';
				$user_mobile = '';
				$description = '';
				$user_price  = '';

				if ( $submission ) {
					$data        = $submission->get_posted_data();
					$user_email  = isset( $data['user_email'] ) ? $data['user_email'] : "";
					$user_mobile = isset( $data['user_mobile'] ) ? $data['user_mobile'] : "";
					$description = isset( $data['description'] ) ? $data['description'] : "";
					$user_price  = isset( $data['user_price'] ) ? $data['user_price'] : "";
				}
				$price = get_post_meta( $postid, "_idpay_cf7_price", TRUE );
				if ( $price == "" ) {
					$price = $user_price;
				}
				$options = get_option( 'idpay_cf7_options' );
				foreach ( $options as $k => $v ) {
					$value[ $k ] = $v;
				}
				$active_gateway    = 'IDPay';
				$url_return        = $value['return'];
				$table_name        = $wpdb->prefix . "cf7_transactions";
				$_x                = array();
				$_x['idform']      = $postid;
				$_x['transid']     = '';
				$_x['gateway']     = $active_gateway;
				$_x['cost']        = $price;
				$_x['created_at']  = time();
				$_x['email']       = $user_email;
				$_x['user_mobile'] = $user_mobile;
				$_x['description'] = $description;
				$_x['status']      = 'none';
				$_y                = array(
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
					$amount  = intval( $price );

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
						'phone'    => $user_mobile,
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

					error_log(print_r($result, true));

					if ( $http_status != 201 || empty( $result ) || empty( $result->id ) || empty( $result->link ) ) {
						$tmp = sprintf( 'خطا هنگام ایجاد تراکنش. کد خطا: %s', $http_status ) . '<br> لطفا به مدیر اطلاع دهید <br><br>';
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
						$_x['transid'] = $result->id;
						$wpdb->insert( $table_name, $_x, $_y );
						Header( 'Location: ' . $result->link );
					}
					exit();
				}
			}
		}
	}
}