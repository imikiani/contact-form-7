<?php

/**
 * @file
 * Contains Service interface.
 */
namespace IDPay\CF7;

/**
 * Interface ServiceInterface
 *
 * We separated some functions and defined them as a service.
 * Every service must define their related hooks in the register method.
 * for example if a service wants to add some admin menus, it must be hooked into
 * the "admin_menu" in it's register() method.
 *
 * @package IDPay\CF7
 */
interface ServiceInterface {

	/**
	 * A place for calling hooks.
	 */
	public function register();
}