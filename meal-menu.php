<?php
/**
 * Plugin Name:       Календарь питания
 * Plugin URI:        https://github.com/igor-blag/meal-menu
 * Description:       Управление школьным меню: шаблоны циклов, календарь питания, генерация Excel для ФЦМПО, общественный контроль питания.
 * Version:           2.0.2
 * Requires PHP:      8.0
 * Requires WP:       6.3
 * Author:            igor-blag
 * Text Domain:       meal-menu
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MEAL_MENU_VERSION', '2.0.2' );
define( 'MEAL_MENU_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEAL_MENU_URL', plugin_dir_url( __FILE__ ) );
define( 'MEAL_MENU_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Путь к публичной папке с XLSX-файлами (с трейлинг-слешем).
 * По умолчанию ABSPATH . 'food/'. Можно изменить в настройках плагина.
 */
function meal_food_dir(): string {
	$custom = get_option( 'meal_food_dir', '' );
	if ( $custom ) {
		return untrailingslashit( $custom ) . '/';
	}
	return untrailingslashit( apply_filters( 'meal_food_dir', ABSPATH . 'food' ) ) . '/';
}

/**
 * Скопировать XLSX-файл в публичную директорию /food/.
 */
function meal_publish_file( string $filepath ): bool {
	if ( ! is_file( $filepath ) ) {
		return false;
	}
	$dir = meal_food_dir();
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	return copy( $filepath, $dir . basename( $filepath ) );
}

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

if ( is_admin() ) {
	new \Meal_Menu\GitHub_Updater( __FILE__, 'igor-blag/meal-menu' );
}

register_activation_hook( __FILE__, array( 'Meal_Menu\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Meal_Menu\\Activator', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Meal_Menu\\Activator', 'uninstall' ) );

add_action( 'plugins_loaded', array( 'Meal_Menu\\Core', 'init' ) );
