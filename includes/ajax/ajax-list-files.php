<?php
try {
	$upload_dir = wp_upload_dir();
	$meal_dir   = $upload_dir['basedir'] . '/meal-menu';

	$departments = $db->get_enabled_departments();
	$files_by_dept = array();

	foreach ( $departments as $dept ) {
		$suffix = $dept['file_suffix'];
		$pattern = $meal_dir . '/*' . $suffix . '.xlsx';
		$matches = glob( $pattern ) ?: array();
		$files = array();

		foreach ( $matches as $filepath ) {
			$files[] = array(
				'name' => basename( $filepath ),
				'url'  => $upload_dir['baseurl'] . '/meal-menu/' . basename( $filepath ),
				'size' => size_format( filesize( $filepath ) ),
				'modified' => gmdate( 'Y-m-d H:i', filemtime( $filepath ) ),
			);
		}

		usort( $files, function ( $a, $b ) {
			return strcmp( $b['name'], $a['name'] );
		} );

		$files_by_dept[ $dept['code'] ] = array(
			'label' => $dept['label'],
			'files' => $files,
		);
	}

	wp_send_json( array( 'ok' => true, 'departments' => $files_by_dept ) );
} catch ( \Exception $e ) {
	wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
}
