<?php

namespace IDPay\CF7;

class Plugin {
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . "cf7_transactions";
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