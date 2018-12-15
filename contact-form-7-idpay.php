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


$plugin = new IDPay\CF7\Plugin();
register_activation_hook( __FILE__, array($plugin, 'activate') );
register_deactivation_hook( __FILE__, array($plugin, 'deactivate') );
