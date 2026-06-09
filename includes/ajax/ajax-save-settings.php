<?php
$action = $data['action'] ?? ( $_POST['action'] ?? '' );

try {
	if ( $action === 'save_settings' ) {
		if ( isset( $data['org_name'] ) ) {
			$db->save_kitchen_settings( sanitize_text_field( $data['org_name'] ) );
		}
		if ( isset( $data['tm_approver_position'] ) || isset( $data['tm_approver_name'] ) ) {
			$db->save_tm_approver(
				sanitize_text_field( $data['tm_approver_position'] ?? '' ),
				sanitize_text_field( $data['tm_approver_name'] ?? '' )
			);
		}
		if ( isset( $data['academic_year_start'] ) || isset( $data['academic_year_end'] ) || isset( $data['reset_cycle_after_vacation'] ) ) {
			$db->save_academic_year_settings(
				sanitize_text_field( $data['academic_year_start'] ?? '09-01' ),
				sanitize_text_field( $data['academic_year_end'] ?? '05-26' ),
				(int) ( $data['reset_cycle_after_vacation'] ?? 0 )
			);
		}

		if ( isset( $data['meal_food_dir'] ) ) {
			$path = sanitize_text_field( $data['meal_food_dir'] );
			if ( $path === '' || $path === ABSPATH . 'food' || $path === ABSPATH . 'food/' ) {
				delete_option( 'meal_food_dir' );
			} else {
				update_option( 'meal_food_dir', untrailingslashit( $path ) );
			}
		}

		$smtp_keys = array( 'meal_admin_email', 'meal_mail_from', 'meal_mail_from_name', 'meal_smtp_host', 'meal_smtp_user', 'meal_smtp_pass', 'meal_smtp_port', 'meal_smtp_secure' );
		foreach ( $smtp_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				update_option( $key, sanitize_text_field( $data[ $key ] ) );
			}
		}

		if ( isset( $data['departments'] ) && is_array( $data['departments'] ) ) {
			foreach ( $data['departments'] as $dept_data ) {
				$id = (int) ( $dept_data['id'] ?? 0 );
				if ( ! $id ) {
					continue;
				}
				$db->save_department( $id, $dept_data );
			}
		}

		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'add_department' ) {
		$code        = sanitize_key( $data['code'] ?? '' );
		$label       = sanitize_text_field( $data['label'] ?? '' );
		$file_suffix = sanitize_text_field( $data['file_suffix'] ?? '' );
		if ( empty( $code ) || empty( $label ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Заполните код и название', 'meal-menu' ) ) );
		}
		$id = $db->add_department( $code, $label, $file_suffix );
		wp_send_json( array( 'ok' => true, 'id' => $id ) );

	} elseif ( $action === 'delete_department' ) {
		$id = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$db->delete_department( $id );
		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'resort_departments' ) {
		$order = is_array( $data['order'] ?? null ) ? $data['order'] : array();
		$db->resort_departments( $order );
		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'add_vacation' ) {
		$academic_year = sanitize_text_field( $data['academic_year'] ?? '' );
		$label         = sanitize_text_field( $data['label'] ?? '' );
		$date_from     = sanitize_text_field( $data['date_from'] ?? '' );
		$date_to       = sanitize_text_field( $data['date_to'] ?? '' );
		$actual_date   = ! empty( $data['actual_date'] ) ? sanitize_text_field( $data['actual_date'] ) : null;
		$id = $db->add_vacation( $academic_year, $label, $date_from, $date_to, $actual_date );
		wp_send_json( array( 'ok' => true, 'id' => $id ) );

	} elseif ( $action === 'update_vacation' ) {
		$id        = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$label     = sanitize_text_field( $data['label'] ?? '' );
		$date_from = sanitize_text_field( $data['date_from'] ?? '' );
		$date_to   = sanitize_text_field( $data['date_to'] ?? '' );
		$actual_date   = isset( $data['actual_date'] ) ? sanitize_text_field( $data['actual_date'] ) : null;
		$db->update_vacation( $id, $label, $date_from, $date_to, $actual_date );
		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'delete_vacation' ) {
		$id = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$db->delete_vacation( $id );
		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'get_vacations' ) {
		$academic_year = sanitize_text_field( $data['academic_year'] ?? $db->get_academic_year_for_date( current_time( 'Y-m-d' ) ) );
		$vacations     = $db->get_vacations( $academic_year );
		wp_send_json( array( 'ok' => true, 'vacations' => $vacations ) );

	} elseif ( $action === 'fill_default_vacations' ) {
		$academic_year = sanitize_text_field( $data['academic_year'] ?? '' );
		if ( empty( $academic_year ) ) {
			wp_send_json( array( 'ok' => false, 'error' => 'Academic year required' ) );
		}
		$parts = explode( '-', $academic_year );
		$y     = (int) $parts[0];

		$ny = $y + 1;
		$defaults = array(
			array( 'Осенние каникулы',
				date_create( "last monday of October $y" )->format( 'Y-m-d' ),
				date_create( "last monday of October $y +6 days" )->format( 'Y-m-d' ),
			),
			array( 'Зимние каникулы',
				date_create( "last monday of December $y" )->format( 'Y-m-d' ),
				date_create( "second monday of January $ny -1 day" )->format( 'Y-m-d' ),
			),
			array( 'Весенние каникулы',
				date_create( "last monday of March $ny" )->format( 'Y-m-d' ),
				date_create( "last monday of March $ny +6 days" )->format( 'Y-m-d' ),
			),
			array( 'Летние каникулы', "$ny-06-01", "$ny-08-31" ),
		);

		foreach ( $defaults as $def ) {
			$db->add_vacation( $academic_year, $def[0], $def[1], $def[2] );
		}

		// Праздники с переносом с выходных на понедельник
		$holidays = array(
			array( 'День народного единства',   "$y-11-04" ),
			array( 'День защитника Отечества',  "$ny-02-23" ),
			array( 'Международный женский день', "$ny-03-08" ),
			array( 'Праздник Весны и Труда',    "$ny-05-01" ),
			array( 'День Победы',               "$ny-05-09" ),
			array( 'День России',               "$ny-06-12" ),
		);
		foreach ( $holidays as $h ) {
			$actual = $h[1];
			$dt  = date_create( $actual );
			$dow = (int) $dt->format( 'w' );
			if ( $dow === 6 ) {       // суббота → понедельник +2
				$dt->modify( '+2 days' );
			} elseif ( $dow === 0 ) { // воскресенье → понедельник +1
				$dt->modify( '+1 days' );
			}
			$date = $dt->format( 'Y-m-d' );
			$actual_date = $date === $actual ? null : $actual;
			$db->add_vacation( $academic_year, $h[0], $date, $date, $actual_date );
		}

		$vacations = $db->get_vacations( $academic_year );
		wp_send_json( array( 'ok' => true, 'vacations' => $vacations, 'message' => __( 'Типовые каникулы добавлены', 'meal-menu' ) ) );

	} elseif ( $action === 'import_data' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Недостаточно прав', 'meal-menu' ) ) );
		}
		$payload = $data['payload'] ?? null;
		if ( ! $payload || ! is_array( $payload ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный формат данных', 'meal-menu' ) ) );
		}

		global $wpdb;
		$p     = $wpdb->prefix . 'meal_';
		$parents = array( 'templates', 'users', 'departments', 'kitchen_settings', 'vacations', 'oc_monitoring' );
		$children = array( 'items', 'calendar', 'email_tokens' );
		$all = array_merge( $children, $parents );

		try {
			foreach ( $all as $table ) {
				$wpdb->query( "DELETE FROM {$p}{$table}" );
			}

			foreach ( $parents as $table ) {
				if ( empty( $payload[ $table ] ) ) {
					continue;
				}
				foreach ( $payload[ $table ] as $row ) {
					$wpdb->insert( "{$p}{$table}", $row );
				}
			}

			foreach ( $children as $table ) {
				if ( empty( $payload[ $table ] ) ) {
					continue;
				}
				foreach ( $payload[ $table ] as $row ) {
					$wpdb->insert( "{$p}{$table}", $row );
				}
			}

			if ( ! empty( $payload['_options'] ) ) {
				foreach ( $payload['_options'] as $opt => $val ) {
					update_option( $opt, $val );
				}
			}
		} catch ( \Exception $e ) {
			wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
		}

		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'reset_data' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Недостаточно прав', 'meal-menu' ) ) );
		}
		\Meal_Menu\Activator::uninstall();
		\Meal_Menu\Activator::activate();
		wp_send_json( array( 'ok' => true ) );

	} else {
		wp_send_json( array( 'ok' => false, 'error' => __( 'Неизвестное действие', 'meal-menu' ) ) );
	}
} catch ( \Exception $e ) {
	wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
}
