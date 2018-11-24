<?php
/*
Plugin Name: IDPay - Contact Form 7
Plugin URI: https://idpay.ir/
Description: درگاه IDPay برای Contact Form 7
Author: Developer: JMDMahdi, Publisher: IDPay
Author URI: https://idpay.ir/
Version: 1.0
*/

class IDPayCF7 {

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

		add_action( 'wpcf7_submit', array( $this, 'let_me_redirect' ) );

		//add_filter( 'wpcf7_contact_form_properties', array($this, 'let_me_redirect'), 10, 2 );

	}

	function let_me_redirect($contact_form) {
		error_log(print_r( "-------------------------- ****[[**** --------------------", true ));
		//$this->fields = $this->get_fields_values( $contact_form->id() );


			$submission   = WPCF7_Submission::get_instance();

			if ( $submission->get_status() == 'mail_sent' ) {

				// Use extrnal url
				if ( $this->fields['external_url'] && $this->fields['use_external_url'] == 'on' ) {
					$this->redirect_url = $this->fields['external_url'];
				} else {
					$this->redirect_url = get_permalink( $this->fields['page_id'] );
				}

				// Pass all fields from the form as URL query parameters
				if ( isset( $this->redirect_url ) && $this->redirect_url ) {
					if ( $this->fields['http_build_query'] == 'on' ) {
						$posted_data  = $submission->get_posted_data();
						// Remove WPCF7 keys from posted data
						$remove_keys  = array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' );
						$posted_data  = array_diff_key( $posted_data, array_flip( $remove_keys ) );
						$this->redirect_url = add_query_arg( $posted_data, $this->redirect_url );
					}
				}

				// Open link in a new tab
				if ( isset( $this->redirect_url ) && $this->redirect_url ) {
					if ( $this->fields['open_in_new_tab'] == 'on' ) {
						$this->enqueue_new_tab_script = true;
					} else {
						wp_redirect( 'https://idpay.ir' );
						exit;
					}
				}
			}

	}


	public function activate() {


		set_transient( 'idpay_cf7_gateway_config_link_shown', TRUE, 5 );
	}

	public function admin_notice() {
		if ( get_transient( 'idpay_cf7_gateway_config_link_shown' ) ) {
			echo "<div class='updated'><p><a href='admin.php?page=idpay_cf7_admin_table'>برای تنظیم اطلاعات درگاه پرداخت آیدی پی کلیک کنید</a>.</p></div>";
			delete_transient( 'idpay_cf7_gateway_config_link_shown' );
		}
	}

	public function admin_menu() {
		add_submenu_page( 'wpcf7',
			__( 'تنظیمات IDPay', 'IDPay' ),
			__( 'تنظیمات IDPay', 'IDPay' ),
			'wpcf7_edit_contact_forms', 'idpay_cf7_gateway_config',
			array( $this, 'gateway_config' ) );

		add_submenu_page( 'wpcf7',
			__( 'تراکنش های IDPay', 'IDPay' ),
			__( 'تراکنش های IDPay', 'IDPay' ),
			'wpcf7_edit_contact_forms', 'idpay_cf7_admin_list_trans',
			array( $this, 'list_transactions' ) );

	}

	public function gateway_config() {
		if ( ! current_user_can( "manage_options" ) ) {
			wp_die( __( "You do not have sufficient permissions to access this page." ) );
		}

		if ( isset( $_POST['update'] ) ) {

			$options['api_key']         = sanitize_text_field( $_POST['api_key'] );
			$options['return-page-id']  = ( intval( $_POST['return-page-id'] ) );
			$options['return']          = get_page_link( intval( $_POST['return-page-id'] ) );
			$options['success_massage'] = wp_filter_post_kses( $_POST['success_massage'] );
			$options['failed_massage']  = wp_filter_post_kses( $_POST['failed_massage'] );

			if ( ! empty( $_POST['sandbox'] ) ) {
				$options['sandbox'] = 1;
			} else {
				$options['sandbox'] = 0;
			}

			update_option( "idpay_cf7_options", $options );

			echo "<br><div class='updated'><p><strong>";
			_e( "Settings Updated." );
			echo "</strong></p></div>";
		}

		$options = get_option( 'idpay_cf7_options' );
		foreach ( $options as $k => $v ) {
			$value[ $k ] = $v;
		}

		$success_massage = ( ! empty( $value['success_massage'] ) ? $value['success_massage'] : 'پرداخت شما با موفقیت انجام شد. کد رهگیری: {track_id}' );
		$failed_massage  = ( ! empty( $value['failed_massage'] ) ? $value['failed_massage'] : 'پرداخت شما ناموفق بوده است. لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.' );
		$return_page_id  = ( ! empty( $value['return-page-id'] ) ? $value['return-page-id'] : 0 );
		$api_key         = ( ! empty( $value['api_key'] ) ? $value['api_key'] : "" );
		if ( $value['sandbox'] == 1 ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		echo "<h2>Contact Form 7 - IDPay Gateway Settings</h2>";
		echo "<table width='100%'><tr><td>" . '<form method="post" enctype="multipart/form-data">';
		echo '<table id=idpay_main_setting_table""> 
<tr>
		  <td><b>API KEY</b></td>
            <td>
            <input type="input" name="api_key" value="' . $api_key . '">
            </td>
          </tr>

         <tr>
		  <td><b>آزمایشگاه</b></td>
            <td>
            <input type="checkbox" name="sandbox" value="1" ' . $checked . '>
            </td>
          </tr>

          <tr>
            <td><b>صفحه بازگشت از تراکنش :</b></td>
            <td>
            ' . wp_dropdown_pages( [
				'depth'                 => 0,
				'child_of'              => 0,
				'selected'              => $return_page_id,
				'echo'                  => 0,
				'name'                  => 'return-page-id',
				'id'                    => NULL,
				'class'                 => NULL,
				'show_option_none'      => NULL,
				'show_option_no_change' => NULL,
				'option_none_value'     => NULL,
			] ) . '<br>کد [idpay_cf7_result] را در صفحه انتخابی قرار دهید
            </td>
         </tr>
		  <tr>
            <td><b>پیام تراکنش موفق :</b></td>
            <td>
			<textarea name="success_massage" dir="auto">' . $success_massage . '</textarea>
			<br>
متن پیامی که می خواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد کنید. همچنین می توانید از شورت کدهای {order_id} برای نمایش شماره سفارش و {track_id} برای نمایش کد رهگیری آیدی پی استفاده نمایید.
            </td>
          </tr>
          <tr></tr>
           <tr>
            <td><b>پیام تراکنش ناموفق :</b></td>
            <td>
			<textarea name="failed_massage" dir="auto">' . $failed_massage . '</textarea>
			<br>
متن پیامی که می خواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد کنید. همچنین می توانید از شورت کدهای {order_id} برای نمایش شماره سفارش و {track_id} برای نمایش کد رهگیری آیدی پی استفاده نمایید.
            </tr>
		   <tr>
          <td>
          <input type="submit" name="btn2" class="button-primary" style="font-size: 17px;line-height: 28px;height: 32px;float: right;" value="ذخیره">
          </td>
          </tr>
        </table>
		<input type="hidden" name="update">
		</form>		
		</td></tr></table>';
	}


	public function list_transactions() {
		if ( ! current_user_can( "manage_options" ) ) {
			wp_die( __( "You do not have sufficient permissions to access this page." ) );
		}

		global $wpdb;
		$pagenum    = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$limit      = 6;
		$offset     = ( $pagenum - 1 ) * $limit;
		$table_name = $wpdb->prefix . "cfZ7_transaction";

		$transactions = $wpdb->get_results( "SELECT * FROM $table_name where (status NOT like 'none') ORDER BY $table_name.id DESC LIMIT $offset, $limit", ARRAY_A );
		$total        = $wpdb->get_var( "SELECT COUNT($table_name.id) FROM $table_name where (status NOT like 'none') " );
		$num_of_pages = ceil( $total / $limit );
		$cntx         = 0;

		echo '<div class="wrap">
		<h2>تراکنش فرم ها</h2>
		<table class="widefat post fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="name" width="15%" class="manage-column" style="">نام فرم</th>
					<th scope="col" id="name" width="" class="manage-column" style="">تاريخ</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">ایمیل</th>
                    <th scope="col" id="name" width="15%" class="manage-column" style="">مبلغ</th>
					<th scope="col" id="name" width="15%" class="manage-column" style=""> کد پیگیری/آی دی پرداخت</th>
					<th scope="col" id="name" width="13%" class="manage-column" style="">وضعیت</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" id="name" width="15%" class="manage-column" style="">نام فرم</th>
					<th scope="col" id="name" width="" class="manage-column" style="">تاريخ</th>
                    <th scope="col" id="name" width="" class="manage-column" style="">ایمیل</th>
                    <th scope="col" id="name" width="15%" class="manage-column" style="">مبلغ</th>
					<th scope="col" id="name" width="15%" class="manage-column" style="">کد پیگیری</th>
					<th scope="col" id="name" width="13%" class="manage-column" style="">وضعیت</th>
				</tr>
			</tfoot>
			<tbody>';

		if ( count( $transactions ) == 0 ) {

			echo '<tr class="alternate author-self status-publish iedit" valign="top">
					<td colspan="6">هيج تراکنش وجود ندارد.</td>
				</tr>';

		} else {
			foreach ( $transactions as $transaction ) {

				echo '<tr class="alternate author-self status-publish iedit" valign="top">
					<td>' . get_the_title( $transaction['idform'] ) . '</td>';
				echo '<td style="direction: ltr; text-align: right;" >' . date( "Y-m-d H:i:s", $transaction['created_at'] );
				echo '<br>(';
				date_default_timezone_set( "Asia/Tehran" );
				$ttime = time() - $transaction["created_at"];
				if ( $ttime < 1 ) {
					echo '0 ثانیه';
				}
				$a = array(
					12 * 30 * 24 * 60 * 60 => 'سال',
					30 * 24 * 60 * 60      => 'ماه',
					24 * 60 * 60           => 'روز',
					60 * 60                => 'ساعت',
					60                     => 'دقیقه',
					1                      => 'ثانیه',
				);
				foreach ( $a as $secs => $str ) {
					$d = $ttime / $secs;
					if ( $d >= 1 ) {
						$r = round( $d );
						echo $r . ' ' . $str . ( $r > 1 ? ' ' : '' );
					}
				}
				echo ' قبل)</td>';

				echo '<td>' . $transaction['email'] . '</td>';
				echo '<td>' . $transaction['cost'] . ' ریال</td>';
				echo '<td>' . $transaction['transid'] . '</td>';
				echo '<td>';

				if ( $transaction['status'] == "success" ) {
					echo '<b style="color:#0C9F55">موفقیت آمیز</b>';
				} else {
					echo '<b style="color:#f00">انجام نشده</b>';
				}
				echo '</td></tr>';

			}
		}
		echo '</tbody>
		</table>
        <br>';

		$page_links = paginate_links( array(
			'base'      => add_query_arg( 'pagenum', '%#%' ),
			'format'    => '',
			'prev_text' => __( '&laquo;', 'aag' ),
			'next_text' => __( '&raquo;', 'aag' ),
			'total'     => $num_of_pages,
			'current'   => $pagenum,
		) );

		if ( $page_links ) {
			echo '<center><div class="tablenav"><div class="tablenav-pages"  style="float:none; margin: 1em 0">' . $page_links . '</div></div>
		</center>';
		}
		echo '<br>
		<hr>
	</div>';
	}

	public function cf7_mail_sent( $cf7 ) {

		error_log( print_r( "-------------------------- In function --------------------", true ) );
		error_log( print_r( $cf7->id(), true ) );

		error_log( print_r( "-------------------------- End --------------------", true ) );
		header('https://google.com');


		global $postid;
		$postid = $cf7->id();

		var_dump( $cf7 );
		die();

		$enable = get_post_meta( $postid, "_idpay_cf7_enable", TRUE );
		$email  = get_post_meta( $postid, "_idpay_cf7_email", TRUE );

		if ( $enable == "1" ) {
			if ( $email == "2" ) {
				global $wpdb;
				global $postid;

				$wpcf7       = WPCF7_ContactForm::get_current();
				$submission  = WPCF7_Submission::get_instance();
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
				$table_name        = $wpdb->prefix . "cfZ7_transaction";
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

$idpay_cf7 = new IDPayCF7;
register_activation_hook( __FILE__, array( $idpay_cf7, 'activate' ) );


