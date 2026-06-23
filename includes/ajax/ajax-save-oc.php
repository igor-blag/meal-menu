<?php
$data = stripslashes_deep( $_POST );

try {
	$db->save_oc_monitoring( $data );

	\Meal_Menu\Shortcodes::invalidate_oc_cache();

	if ( ! $db->is_kindergarten() && class_exists( '\Meal_Menu\Excel_OC' ) ) {
		\Meal_Menu\Excel_OC::generate();
	}

	wp_send_json( array( 'ok' => true ) );
} catch ( \Exception $e ) {
	wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
}
