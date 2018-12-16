<?php

namespace IDPay\CF7;
use IDPay\CF7\Admin\AdditionalSettingsForm;
use IDPay\CF7\Admin\Menu;
use IDPay\CF7\Payment\Payment;
use IDPay\CF7\Payment\Callback;

class Init {

	public static function call_services() {
		foreach ( self::discover() as $class ) {
			/** @var \IDPay\CF7\ServiceInterface $service */
			$service = self::instantiate( $class );
			$service->register();
		}
	}

	private static function discover() {
		return array(
			AdditionalSettingsForm::class,
			Callback::class,
			Menu::class,
			Payment::class,
		);
	}

	private static function instantiate( $class ) {
		/** @var \IDPay\CF7\ServiceInterface $service */
		$service = new $class();

		return $service;
	}

}