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

use IDPay\CF7\Init;
use IDPay\CF7\Plugin;

define( 'CF7_IDPAY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
	Init::call_services();
}

function cf7_idpay_activate() {
	Plugin::activate();
}

function cf7_idpay_deactivate() {
	Plugin::deactivate();
}

$plugin = new IDPay\CF7\Plugin();
register_activation_hook( __FILE__, 'cf7_idpay_activate' );
register_deactivation_hook( __FILE__, 'cf7_idpay_deactivate' );
