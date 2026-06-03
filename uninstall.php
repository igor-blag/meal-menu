<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-db.php';

Meal_Menu\DB::drop_tables();

$role = get_role( 'meal_manager' );
if ( $role ) {
	remove_role( 'meal_manager' );
}

delete_option( 'meal_menu_settings' );
delete_option( 'meal_delete_after_days' );
delete_option( 'meal_mail_from' );
delete_option( 'meal_mail_from_name' );
delete_option( 'meal_admin_email' );
delete_option( 'meal_token_ttl' );
delete_option( 'meal_smtp_host' );
delete_option( 'meal_smtp_user' );
delete_option( 'meal_smtp_pass' );
delete_option( 'meal_smtp_port' );
delete_option( 'meal_smtp_secure' );

$upload_dir = wp_upload_dir();
$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
if ( is_dir( $meal_dir ) ) {
	array_map( 'unlink', glob( "$meal_dir/**/*" ) ?: array() );
	rmdir( $meal_dir );
}
