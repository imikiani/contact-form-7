<?php


namespace IDPay\CF7\Admin;
use IDPay\CF7\ServiceInterface;

class AdditionalSettingsForm implements ServiceInterface {

	public function register() {
		add_filter( 'wpcf7_editor_panels', array(
			$this,
			'editor_panels',
		) );
		add_action( 'wpcf7_save_contact_form', array(
			$this,
			'save',
		) );
	}

	public function render( $cf7 ) {
		$post_id = sanitize_text_field( $_GET['post'] );
		$enable  = get_post_meta( $post_id, "_idpay_cf7_enable", TRUE );
		$price   = get_post_meta( $post_id, "_idpay_cf7_price", TRUE );
		$email   = get_post_meta( $post_id, "_idpay_cf7_email", TRUE );
		if ( $enable == "1" ) {
			$checked = "CHECKED";
		} else {
			$checked = "";
		}
		$admin_table_output = "";
		$admin_table_output .= "<form>";
		$admin_table_output .= "<div id='additional_settings-sortables' class='meta-box-sortables ui-sortable'><div id='additionalsettingsdiv' class='postbox'>";
		$admin_table_output .= "<div class='handlediv' title='Click to toggle'><br></div><h3 class='hndle ui-sortable-handle'> <span>اطلاعات پرداخت برای فرم</span></h3>";
		$admin_table_output .= "<div class='inside'>";
		$admin_table_output .= "<div class='mail-field'>";
		$admin_table_output .= "<input name='enable' id='idpay_active' value='1' type='checkbox' $checked>";
		$admin_table_output .= "<label for='idpay_active'>فعال سازی امکان پرداخت آنلاین</label>";
		$admin_table_output .= "</div>";
		$admin_table_output .= "<table>";
		$admin_table_output .= "<tr><td>مبلغ: </td><td><input type='text' name='price' style='text-align:left;direction:ltr;' value='$price'></td><td>(ریال)</td></tr>";
		$admin_table_output .= "</table>";
		$admin_table_output .= "<br> برای اتصال به درگاه پرداخت میتوانید از فیلدهای زیر استفاده نمایید ";
		$admin_table_output .= "<br>
        <span style='color:#F00;'>
        user_email فیلد یمیل کاربر.
        <br>
         description فیلد توضیحات.
        <br>
         user_mobile فیلد شماره تلفن کاربر.
        <br>
        user_price فیلد مبلغ دلخواه کاربر (در صورتی که کادر مبلغ خالی باشد قال استفاده است)
        </span>	";
		$admin_table_output .= "<input type='hidden' name='email' value='2'>";
		$admin_table_output .= "<input type='hidden' name='post' value='$post_id'>";
		$admin_table_output .= "</td></tr></table></form>";
		$admin_table_output .= "</div>";
		$admin_table_output .= "</div>";
		$admin_table_output .= "</div>";
		echo $admin_table_output;
	}

	public function save( $cf7 ) {
		$post_id = sanitize_text_field( $_POST['post'] );
		if ( ! empty( $_POST['enable'] ) ) {
			$enable = sanitize_text_field( $_POST['enable'] );
			update_post_meta( $post_id, "_idpay_cf7_enable", $enable );
		} else {
			update_post_meta( $post_id, "_idpay_cf7_enable", 0 );
		}
		$price = sanitize_text_field( $_POST['price'] );
		update_post_meta( $post_id, "_idpay_cf7_price", $price );
		$email = sanitize_text_field( $_POST['email'] );
		update_post_meta( $post_id, "_idpay_cf7_email", $email );
	}

	public function editor_panels( $panels ) {
		$new_page = array(
			'PricePay' => array(
				'title'    => __( 'اطلاعات پرداخت', 'IDPay' ),
				'callback' => array( $this, 'render' ),
			),
		);
		$panels   = array_merge( $panels, $new_page );

		return $panels;
	}

}