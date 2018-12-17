<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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