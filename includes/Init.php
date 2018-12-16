<?php

namespace IDPay\CF7;

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