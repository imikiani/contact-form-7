<?php
/*
Plugin Name: IDPay - Contact Form 7
Plugin URI: https://idpay.ir/
Description: درگاه IDPay برای Contact Form 7
Author: Developer: JMDMahdi, Publisher: IDPay
Author URI: https://idpay.ir/
Version: 1.0
*/


require_once 'vendor/autoload.php';

$IDPay_Callback = new IDPay\CF7\Callback();
add_shortcode( 'idpay_cf7_result', array( $IDPay_Callback, 'handler' ) );

$dir = plugin_dir_path( __FILE__ );
register_activation_hook( __FILE__, "idpay_cf7_activate" );
register_deactivation_hook( __FILE__, "idpay_cf7_deactivate" );
register_uninstall_hook( __FILE__, "idpay_cf7_uninstall" );

function idpay_cf7_activate() {
	global $wpdb;
	$table_name = $wpdb->prefix . "cfZ7_transaction";
	if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(11) NOT NULL AUTO_INCREMENT,
			idform bigint(11) DEFAULT '0' NOT NULL,
			transid VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			gateway VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			cost bigint(11) DEFAULT '0' NOT NULL,
			created_at bigint(11) DEFAULT '0' NOT NULL,
			email VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL,
			description VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			user_mobile VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL,
			status VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
			PRIMARY KEY id (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	function wp_config_put( $slash = '' ) {
		$config = file_get_contents( ABSPATH . "wp-config.php" );
		$config = preg_replace( "/^([\r\n\t ]*)(\<\?)(php)?/i", "<?php define('WPCF7_LOAD_JS', false);", $config );
		file_put_contents( ABSPATH . $slash . "wp-config.php", $config );
	}

	if ( file_exists( ABSPATH . "wp-config.php" ) && is_writable( ABSPATH . "wp-config.php" ) ) {
		wp_config_put();
	} else if ( file_exists( dirname( ABSPATH ) . "/wp-config.php" ) && is_writable( dirname( ABSPATH ) . "/wp-config.php" ) ) {
		wp_config_put( '/' );
	} else {
		?>
        <div class="error">
            <p><?php _e( 'wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been activated.', 'cf7pp' ); ?></p>
        </div>
		<?php
		exit;
	}

	$idpay_cf7_options = array(
		'api_key' => '',
		'return'  => '',
		'sandbox' => '1',
	);

	add_option( "idpay_cf7_options", $idpay_cf7_options );
}


function idpay_cf7_deactivate() {

	function wp_config_delete( $slash = '' ) {
		$config = file_get_contents( ABSPATH . "wp-config.php" );
		$config = preg_replace( "/( ?)(define)( ?)(\()( ?)(['\"])WPCF7_LOAD_JS(['\"])( ?)(,)( ?)(0|1|true|false)( ?)(\))( ?);/i", "", $config );
		file_put_contents( ABSPATH . $slash . "wp-config.php", $config );
	}

	if ( file_exists( ABSPATH . "wp-config.php" ) && is_writable( ABSPATH . "wp-config.php" ) ) {
		wp_config_delete();
	} else if ( file_exists( dirname( ABSPATH ) . "/wp-config.php" ) && is_writable( dirname( ABSPATH ) . "/wp-config.php" ) ) {
		wp_config_delete( '/' );
	} else if ( file_exists( ABSPATH . "wp-config.php" ) && ! is_writable( ABSPATH . "wp-config.php" ) ) {
		?>
        <div class="error">
            <p><?php _e( 'wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7pp' ); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
          function goBack() {
            window.history.back();
          }
        </script>
		<?php
		exit;
	} else if ( file_exists( dirname( ABSPATH ) . "/wp-config.php" ) && ! is_writable( dirname( ABSPATH ) . "/wp-config.php" ) ) {
		?>
        <div class="error">
            <p><?php _e( 'wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7pp' ); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
          function goBack() {
            window.history.back();
          }
        </script>
		<?php
		exit;
	} else {
		?>
        <div class="error">
            <p><?php _e( 'wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7pp' ); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
          function goBack() {
            window.history.back();
          }
        </script>
		<?php
		exit;
	}

	delete_option( "idpay_cf7_options" );
	delete_option( "idpay_cf7_my_plugin_notice_shown" );
}


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {


	$IDPay_CF7_Menu = new IDPay\CF7\Menu();
	add_action( 'admin_menu', array( $IDPay_CF7_Menu, 'admin_menu' ) );

	$IDPay_Payment = new IDPay\CF7\Payment();
	add_action( 'wpcf7_mail_sent', array( $IDPay_Payment, 'after_send_mail' ) );


	$IDPay_CF7_Additional_settings = new IDPay\CF7\AdditionalSettingsForm();


	add_filter( 'wpcf7_editor_panels', array(
		$IDPay_CF7_Additional_settings,
		'editor_panels',
	) );
	add_action( 'wpcf7_save_contact_form', array(
		$IDPay_CF7_Additional_settings,
		'save',
	) );


}
?>
