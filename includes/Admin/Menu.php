<?php

namespace IDPay\CF7\Admin;
use IDPay\CF7\ServiceInterface;

class Menu implements ServiceInterface {
	public function register() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		add_submenu_page( 'wpcf7',
			__( 'تنظیمات IDPay', 'IDPay' ),
			__( 'تنظیمات IDPay', 'IDPay' ),
			'wpcf7_edit_contact_forms', 'idpay_cf7_admin_table',
			array( $this, 'admin_table' ) );

		add_submenu_page( 'wpcf7',
			__( 'لیست تراکنش ها', 'IDPay' ),
			__( 'لیست تراکنش ها', 'IDPay' ),
			'wpcf7_edit_contact_forms', 'idpay_cf7_admin_list_trans',
			array( $this, 'list_trans' ) );

	}


	public function admin_table() {
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

	public function list_trans() {
		if ( ! current_user_can( "manage_options" ) ) {
			wp_die( __( "You do not have sufficient permissions to access this page." ) );
		}

		global $wpdb;
		$pagenum    = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$limit      = 6;
		$offset     = ( $pagenum - 1 ) * $limit;
		$table_name = $wpdb->prefix . "cf7_transactions";

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
}