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

				if ( empty( $amount ) ):
					?>
                    <a href="<?php echo get_option( 'siteurl' ) ?>"><?php _e( 'Go back to the site!', 'idpay-contact-form-7' ) ?></a>
                    <html>
                    <head>
                        <meta http-equiv="Content-Type"
                              content="text/html; charset=utf-8"/>
                        <title><?php _e( 'Error in payment operation.', 'idpay-contact-form-7' ) ?></title>
                    </head>
                    <link rel="stylesheet" media="all" type="text/css"
                          href="<?php echo plugins_url( 'style.css', __FILE__ ) ?>">
                    <body>
                    <div>
                        <h3><?php _e( 'Error in payment operation.', 'idpay-contact-form-7' ) ?></h3>
                        <h4><?php _e( 'Amount can not be empty.', 'idpay-contact-form-7' ) ?></h4>
                    </div>
                    </body>
                    </html>
					<?php
					exit();
				endif;

				$data = array(
					'order_id' => time(),
					'amount'   => $amount,
					'phone'    => $phone,
					'desc'     => $desc,
					'callback' => $url_return,
				);

				$ch = curl_init( 'https://api.idpay.ir/v1.1/payment' );
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

				if ( $http_status != 201 || empty( $result ) || empty( $result->id ) || empty( $result->link ) ):
					?>
                    <html>
                    <head>
                        <meta http-equiv="Content-Type"
                              content="text/html; charset=utf-8"/>
                        <title><?php _e( 'Error in payment operation.', 'idpay-contact-form-7' ) ?></title>
                    </head>
                    <link rel="stylesheet" media="all" type="text/css"
                          href="<?php echo plugins_url( 'style.css', __FILE__ ) ?>">
                    <body>
                    <div>
                        <h3><?php _e( 'Error in payment operation.', 'idpay-contact-form-7' ) ?></h3>
                        <h4><?php echo sprintf( __( 'An error occurred while creating a transaction. error status: %s, error code: %s, error message: %s', 'idpay-contact-form-7' ), $http_status, $result->error_code, $result->error_message ) ?></h4>
                        <a href="<?php echo get_option( 'siteurl' ) ?>"><?php _e( 'Go back to the site!', 'idpay-contact-form-7' ) ?></a>
                    </div>
                    </body>
                    </html>
				<?php
				else:
					$row['trans_id'] = $result->id;
					$wpdb->insert( $table_name, $row, $row_format );
					Header( 'Location: ' . $result->link );
				endif;
				exit();
			}

		}
	}
}