<?php
try {
	if ( empty( $_FILES['file'] ) ) {
		wp_send_json( array( 'ok' => false, 'error' => __( 'Файл не загружен', 'meal-menu' ) ) );
	}

	$file = $_FILES['file'];
	$allowed_ext = array( 'pdf', 'jpg', 'jpeg', 'png', 'webp' );
	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

	if ( ! in_array( $ext, $allowed_ext, true ) ) {
		wp_send_json( array( 'ok' => false, 'error' => __( 'Недопустимый формат файла', 'meal-menu' ) ) );
	}

	if ( $file['size'] > 20 * 1024 * 1024 ) {
		wp_send_json( array( 'ok' => false, 'error' => __( 'Файл больше 20MB', 'meal-menu' ) ) );
	}

	$upload_dir = wp_upload_dir();
	$oc_dir     = $upload_dir['basedir'] . '/meal-menu/oc';
	wp_mkdir_p( $oc_dir );

	$uniqid = uniqid( 'oc_', true );
	$dest   = $oc_dir . '/' . $uniqid . '.' . $ext;

	$contents = file_get_contents( $file['tmp_name'] );

	if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
		$image = wp_get_image_editor( $file['tmp_name'] );
		if ( ! is_wp_error( $image ) ) {
			$image->resize( 2000, 2000, false );
			$image->save( $dest, 'image/jpeg' );
		} else {
			file_put_contents( $dest, $contents );
		}
	} else {
		file_put_contents( $dest, $contents );
	}

	$url = $upload_dir['baseurl'] . '/meal-menu/oc/' . $uniqid . '.' . $ext;
	wp_send_json( array( 'ok' => true, 'url' => $url, 'filename' => $uniqid . '.' . $ext ) );
} catch ( \Exception $e ) {
	wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
}
