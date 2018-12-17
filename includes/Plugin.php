<?php
/**
 * @file Contains Plugin class.
 */

namespace IDPay\CF7;

/**
 * Class Plugin
 * Defines some common actions such as activating and deactivating a plugin.
 *
 * @package IDPay\CF7
 */
class Plugin {

	/**
	 * This is triggered when the plugin is going to be activated.
	 *
	 * Creates a table in database which stores all transactions.
	 *
	 * Also defines a variable in the 'wp-config.php' file so that
	 * any contact form does not load javascript files in order to disabling
	 * ajax capability of those form. This is happened so that we can redirect
	 * to the gateway for processing a payment. => define('WPCF7_LOAD_JS',
	 * false);
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . "cf7_transactions";
		if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(11) NOT NULL AUTO_INCREMENT,
			form_id bigint(11) DEFAULT '0' NOT NULL,
			trans_id VARCHAR(255) NOT NULL,
			track_id VARCHAR(255) NULL,
			gateway VARCHAR(255) NOT NULL,
			amount bigint(11) DEFAULT '0' NOT NULL,
			phone VARCHAR(11) NULL,
			description VARCHAR(255) NOT NULL,
			email VARCHAR(255) NULL,
			created_at bigint(11) DEFAULT '0' NOT NULL,
			status VARCHAR(255) NOT NULL,
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

	/**
	 * This is triggered when the plugin is going to be deactivated.
	 */
	public static function deactivate() {

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
	}
}