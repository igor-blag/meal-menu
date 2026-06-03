<?php
/**
 * Plugin Name:       Мониторинг питания
 * Plugin URI:        https://github.com/igor-blag/web-food
 * Description:       Управление школьным меню: шаблоны циклов, календарь питания, генерация Excel для ФЦМПО, общественный контроль питания.
 * Version:           1.0.0
 * Requires PHP:      8.0
 * Requires WP:       6.3
 * Author:            igor-blag
 * Text Domain:       meal-menu
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MEAL_MENU_VERSION', '1.0.0' );
define( 'MEAL_MENU_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEAL_MENU_URL', plugin_dir_url( __FILE__ ) );
define( 'MEAL_MENU_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register( function ( $class ) {
	$prefix = 'Meal_Menu\\';
	if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
		return;
	}
	$relative_class = substr( $class, strlen( $prefix ) );
	$file           = MEAL_MENU_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

require_once MEAL_MENU_DIR . 'includes/class-db.php';

register_activation_hook( __FILE__, array( 'Meal_Menu\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Meal_Menu\\Activator', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Meal_Menu\\Activator', 'uninstall' ) );

add_action( 'plugins_loaded', array( 'Meal_Menu\\Core', 'init' ) );
