<?php
$data   = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
$action = $data['action'] ?? ( $_POST['action'] ?? '' );

$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );
$dept_by_code  = array();
foreach ( $enabled_depts as $dep ) {
	$dept_by_code[ $dep['code'] ] = $dep;
}

try {
	if ( $action === 'save' ) {
		$date   = $data['date']   ?? '';
		$type   = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$school = $data['school'] ?? null;
		$dept   = $data['dept']   ?? null;
		$is_school      = ! empty( $data['is_school'] );
		$day_num        = isset( $data['day_num'] ) ? (int) $data['day_num'] : 0;
		$is_cycle_start = ! empty( $data['is_cycle_start'] ) ? 1 : 0;

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверная дата', 'meal-menu' ) ) );
		}

		if ( ! $is_school ) {
			$db->save_calendar_day( $date, null, $school, $dept, $type, 0 );
			wp_send_json( array( 'ok' => true, 'template_id' => null, 'day_number' => null ) );
		}

		if ( $day_num < 1 ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Укажите день меню', 'meal-menu' ) ) );
		}

		$templates = $db->get_templates_ordered( $type );
		if ( ! isset( $templates[ $day_num ] ) ) {
			wp_send_json( array( 'ok' => false, 'error' => sprintf( __( 'Шаблон дня %d не найден для типа %s', 'meal-menu' ), $day_num, $type ) ) );
		}
		$tpl_id = $templates[ $day_num ];
		$db->save_calendar_day( $date, $tpl_id, $school, $dept, $type, $is_cycle_start );

		$xls_path = null;
		if ( ! empty( $dept_by_code[ $type ]['publish_xlsx'] ) ) {
			$xls_path = class_exists( '\Meal_Menu\Excel_Daily' ) ? \Meal_Menu\Excel_Daily::generate( $date, $type ) : null;
		}

		$entry = $db->get_calendar_day( $date, $type );
		wp_send_json( array(
			'ok'          => true,
			'template_id' => $tpl_id,
			'day_number'  => $day_num,
			'label'       => $entry['template_label'] ?? "День {$day_num}",
			'xls'         => $xls_path ? basename( $xls_path ) : null,
		) );

	} elseif ( $action === 'delete' ) {
		$date = $data['date'] ?? '';
		$type = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверная дата', 'meal-menu' ) ) );
		}
		$db->delete_calendar_day( $date, $type );
		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'apply_cycle' ) {
		$date      = $data['date']      ?? '';
		$type      = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$start_day = isset( $data['start_day'] ) ? (int) $data['start_day'] : 1;
		$school    = $data['school']   ?? null;
		$dept      = $data['dept']     ?? null;
		$end_date  = $data['end_date'] ?? null;

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверная дата', 'meal-menu' ) ) );
		}
		if ( $end_date !== null && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			$end_date = null;
		}

		$workdays = isset( $dept_by_code[ $type ] ) ? $db->get_workdays( $type ) : array( 1, 2, 3, 4, 5 );
		$count    = $db->assign_cycle( $date, $start_day, $type, $school, $dept, $end_date, $workdays );
		wp_send_json( array( 'ok' => true, 'count' => $count ) );

	} elseif ( $action === 'bulk_save' ) {
		$type = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$days = is_array( $data['days'] ?? null ) ? $data['days'] : array();
		$db->bulk_save_calendar( $days, $type );
		wp_send_json( array( 'ok' => true, 'saved' => count( $days ) ) );

	} elseif ( $action === 'recalc_period' ) {
		$type   = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$dept   = $dept_by_code[ $type ] ?? null;
		$school = $data['school'] ?? null;
		$dept_name = $data['dept'] ?? null;

		$period    = $db->get_current_period( $type );
		$templates = $db->get_templates_ordered( $type );
		$cycle_len = count( $templates );

		if ( $cycle_len === 0 ) {
			wp_send_json( array( 'ok' => false, 'error' => sprintf( __( 'Нет шаблонов для типа %s', 'meal-menu' ), $type ) ) );
		}

		$keys           = array_keys( $templates );
		$workdays       = $dept ? $db->get_workdays( $type ) : array( 1, 2, 3, 4, 5 );
		$ay_settings    = $db->get_academic_year_settings();
		$reset_after_vac = (bool) $ay_settings['reset_cycle_after_vacation'];
		$academic_year  = $db->get_academic_year_for_date( $period['from'] );
		$vacation_days  = $db->get_vacation_days_for_range( $period['from'], $period['to'] );

		$c            = $db->get_table_name( 'calendar' );
		$t            = $db->get_table_name( 'templates' );
		global $wpdb;
		$last_before  = $wpdb->get_row( $wpdb->prepare(
			"SELECT t.day_number FROM $c c
			 LEFT JOIN $t t ON t.id = c.template_id
			 WHERE c.date < %s AND c.school_type = %s AND c.template_id IS NOT NULL
			 ORDER BY c.date DESC LIMIT 1",
			$period['from'], $type
		), ARRAY_A );

		$start_idx = 0;
		if ( $last_before && $last_before['day_number'] && ! $reset_after_vac ) {
			$last_day_num = (int) $last_before['day_number'];
			$pos = array_search( $last_day_num, $keys, true );
			if ( $pos !== false ) {
				$start_idx = ( $pos + 1 ) % $cycle_len;
			}
		}

		$cur = new \DateTime( $period['from'] );
		$end = new \DateTime( $period['to'] );
		$idx = $start_idx;
		$days = array();

		while ( $cur <= $end ) {
			$date_str   = $cur->format( 'Y-m-d' );
			$wday       = (int) $cur->format( 'N' );
			$is_workday = in_array( $wday, $workdays, true );
			$is_vac     = isset( $vacation_days[ $date_str ] );

			if ( ! $is_vac && $is_workday ) {
				$day_num = $keys[ $idx % $cycle_len ];
				$days[] = array(
					'date'           => $date_str,
					'day_num'        => $day_num,
					'school'         => $school,
					'dept'           => $dept_name,
					'is_cycle_start' => $idx === $start_idx ? 1 : 0,
				);
				$idx++;
			}
			$cur->modify( '+1 day' );
		}

		if ( ! empty( $days ) ) {
			$db->bulk_save_calendar( $days, $type );
		}

		wp_send_json( array( 'ok' => true, 'count' => count( $days ), 'period' => $period ) );

	} elseif ( $action === 'generate_files' ) {
		$type  = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$year  = isset( $data['year'] )  ? (int) $data['year']  : (int) current_time( 'Y' );
		$month = isset( $data['month'] ) ? (int) $data['month'] : (int) current_time( 'n' );

		if ( ! isset( $dept_by_code[ $type ] ) || empty( $dept_by_code[ $type ]['publish_xlsx'] ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Публикация xlsx отключена для этого отделения', 'meal-menu' ) ) );
		}

		$generated = array();
		$days_in_month = (int) ( new \DateTimeImmutable( "$year-$month-01" ) )->format( 't' );
		for ( $d = 1; $d <= $days_in_month; $d++ ) {
			$date_str = sprintf( '%04d-%02d-%02d', $year, $month, $d );
			if ( class_exists( '\Meal_Menu\Excel_Daily' ) ) {
				$path = \Meal_Menu\Excel_Daily::generate( $date_str, $type );
				if ( $path ) {
					$generated[] = basename( $path );
				}
			}
		}
		if ( class_exists( '\Meal_Menu\Excel_KP' ) ) {
			\Meal_Menu\Excel_KP::generate( $year, $type );
		}
		wp_send_json( array( 'ok' => true, 'count' => count( $generated ), 'files' => $generated ) );

	} else {
		wp_send_json( array( 'ok' => false, 'error' => __( 'Неизвестное действие', 'meal-menu' ) ) );
	}
} catch ( \Exception $e ) {
	wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
}
