<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Roles {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'add_role' ) );
	}

	public static function add_role(): void {
		$capability = 'manage_meal_menu';

		add_role(
			'meal_manager',
			__( 'Менеджер питания', 'meal-menu' ),
			array(
				'read'                      => true,
				$capability                 => true,
			)
		);

		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( $capability ) ) {
			$admin->add_cap( $capability );
		}
	}

	public static function remove_role(): void {
		$capability = 'manage_meal_menu';

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->remove_cap( $capability );
		}

		remove_role( 'meal_manager' );
	}
}
