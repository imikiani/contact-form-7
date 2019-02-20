<?php

/**
 * @file Contains Admin AdditionalSettingsForm.
 */

namespace IDPay\CF7\Admin;

use IDPay\CF7\ServiceInterface;

/**
 * Class AdditionalSettingsForm
 * Defines a tab beside other tabs in all contact forms in edit mode.
 *
 * @package IDPay\CF7\Admin
 */
class AdditionalSettingsForm implements ServiceInterface {

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * Renders a tab beside other tabs for a contact form in edit mode.
	 *
	 * @param $cf7
	 *   the contact form 7 instance which is passed through the hook
	 *   'editor_panels'.
	 */
	public function render( $cf7 ) {
		$post_id = sanitize_text_field( $_GET['post'] );
		$enable  = get_post_meta( $post_id, "_idpay_cf7_enable", TRUE );
		$amount  = get_post_meta( $post_id, "_idpay_cf7_amount", TRUE );
		if ( $enable == "1" ) {
			$checked = "CHECKED";
		} else {
			$checked = "";
		}

		require_once( CF7_IDPAY_PLUGIN_PATH . 'templates/additional-settings-form.php' );
	}

	/**
	 * Saves additional settings in the contact form.
	 * Hooks into an event when a contact form is going to be saved.
	 *
	 * @param $cf7
	 *   The contact form must be saved.
	 */
	public function save( $cf7 ) {
		$post_id = sanitize_text_field( $_POST['post'] );
		if ( ! empty( $_POST['idpay_enable'] ) ) {
			$enable = sanitize_text_field( $_POST['idpay_enable'] );
			update_post_meta( $post_id, "_idpay_cf7_enable", $enable );
		} else {
			update_post_meta( $post_id, "_idpay_cf7_enable", 0 );
		}
		$amount = sanitize_text_field( $_POST['idpay_amount'] );
		update_post_meta( $post_id, "_idpay_cf7_amount", $amount );
	}

	/**
	 * Hooks into an event when Contact Form 7 wants to draw all tabs for
	 * a contact form. We want to add the ability of using IDPay payment gateway
	 * in that contact form. Therefore it use the render() method
	 * to draw a new tab beside other tabs in a contact form's edit mode.
	 *
	 * @param $panels
	 *
	 * @return array
	 */
	public function editor_panels( $panels ) {
		$new_page = array(
			'IDPayPanel' => array(
				'title'    => __( 'IDPay payment', 'idpay-contact-form-7' ),
				'callback' => array( $this, 'render' ),
			),
		);
		$panels   = array_merge( $panels, $new_page );

		return $panels;
	}

}