<?php
/**
 * @file Contains Init class.
 */

namespace IDPay\CF7;

use IDPay\CF7\Admin\AdditionalSettingsForm;
use IDPay\CF7\Admin\Menu;
use IDPay\CF7\Payment\Payment;
use IDPay\CF7\Payment\Callback;

/**
 * Class Init
 * This class registers all services and instantiate them.
 *
 * @see     \IDPay\CF7\ServiceInterface
 *
 * @package IDPay\CF7.
 */
class Init {

	public static function call_services() {
		foreach ( self::discover() as $class ) {
			/** @var \IDPay\CF7\ServiceInterface $service */
			$service = self::instantiate( $class );
			$service->register();
		}
	}

	/**
	 * Lists all services.
	 *
	 * @return array
	 */
	private static function discover() {
		return array(
			AdditionalSettingsForm::class,
			Callback::class,
			Menu::class,
			Payment::class,
		);
	}

	/**
	 * Instantiate a class.
	 *
	 * @param $class
	 *   the class must be instantiated.
	 *
	 * @return \IDPay\CF7\ServiceInterface
	 */

	private static function instantiate( $class ) {
		/** @var \IDPay\CF7\ServiceInterface $service */
		$service = new $class();

		return $service;
	}

}