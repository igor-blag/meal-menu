<?php
/**
 * Render callback for the meal calendar block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content (unused).
 * @param WP_Block $block      Block instance.
 * @return string
 */
defined( 'ABSPATH' ) || exit;

$type    = $attributes['type'] ?? '';
$palette = $attributes['palette'] ?? '';
$layout  = $attributes['layout'] ?? '';
$show_oc = isset( $attributes['show_oc'] ) ? (bool) $attributes['show_oc'] : true;

$db = \Meal_Menu\DB::instance();
if ( $db->is_kindergarten() ) {
	$show_oc = false;
}
$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );

if ( ! $type || ! in_array( $type, $valid_types, true ) ) {
	$type = $valid_types[0] ?? 'sm';
}

wp_enqueue_style( 'meal-menu-public', MEAL_MENU_URL . 'assets/css/public.css', array(), MEAL_MENU_VERSION );
wp_enqueue_style( 'meal-menu-themes', MEAL_MENU_URL . 'assets/css/themes.css', array( 'meal-menu-public' ), MEAL_MENU_VERSION );
wp_enqueue_script( 'meal-menu-public', MEAL_MENU_URL . 'assets/js/public.js', array( 'jquery' ), MEAL_MENU_VERSION, true );
wp_localize_script( 'meal-menu-public', 'mealMenu', array(
	'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	'nonce'   => wp_create_nonce( 'meal_menu_nonce' ),
) );

$core = new \Meal_Menu\Core();
echo $core->shortcode_calendar( array(
	'type'    => $type,
	'palette' => $palette,
	'layout'  => $layout,
	'show_oc' => $show_oc,
) );
