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
				sanitize_text_field( $data['academic_year_end'] ?? '05-31' ),
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

		$sync_result = array();
		if ( isset( $data['departments'] ) && is_array( $data['departments'] ) ) {
			foreach ( $data['departments'] as $dept_data ) {
				$id = (int) ( $dept_data['id'] ?? 0 );
				if ( ! $id ) {
					continue;
				}
				$db->save_department( $id, $dept_data );

				$dept_row = $db->get_department_by_id( $id );
				if ( $dept_row && isset( $dept_data['cycle_length'] ) ) {
					$code = $dept_row['code'];
					$sync_result[ $code ] = $db->sync_templates_to_cycle_length( $code, (int) $dept_data['cycle_length'] );
				}
			}
		}

		wp_send_json( array( 'ok' => true, 'sync' => $sync_result ) );

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
		$id = $db->add_vacation( $academic_year, $label, $date_from, $date_to );
		wp_send_json( array( 'ok' => true, 'id' => $id ) );

	} elseif ( $action === 'update_vacation' ) {
		$id        = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$label     = sanitize_text_field( $data['label'] ?? '' );
		$date_from = sanitize_text_field( $data['date_from'] ?? '' );
		$date_to   = sanitize_text_field( $data['date_to'] ?? '' );
		$db->update_vacation( $id, $label, $date_from, $date_to );
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

		$defaults = array(
			array( 'Осенние каникулы',   "$y-10-28", "$y-11-05" ),
			array( 'Зимние каникулы',    ( $y + 1 ) . '-01-01', ( $y + 1 ) . '-01-08' ),
			array( 'Весенние каникулы',  ( $y + 1 ) . '-03-24', ( $y + 1 ) . '-03-31' ),
			array( 'Летние каникулы',    ( $y + 1 ) . '-06-01', ( $y + 1 ) . '-08-31' ),
		);

		foreach ( $defaults as $def ) {
			$db->add_vacation( $academic_year, $def[0], $def[1], $def[2] );
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
