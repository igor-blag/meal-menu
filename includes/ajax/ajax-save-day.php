<?php
$action = $data['action'] ?? ( $_POST['action'] ?? '' );
$is_camp = ! empty( $data['camp'] );

$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );
$dept_by_code  = array();
foreach ( $enabled_depts as $dep ) {
	$dept_by_code[ $dep['code'] ] = $dep;
}

	function get_merge_types( $type, $dept_by_code ) {
		$types = array( $type );
		$merge_with = ! empty( $dept_by_code[ $type ]['merged_with'] ) ? $dept_by_code[ $type ]['merged_with'] : null;
		if ( $merge_with && isset( $dept_by_code[ $merge_with ] ) && $merge_with !== $type ) {
			$types[] = $merge_with;
		}
		foreach ( $dept_by_code as $code => $dep ) {
			if ( $code !== $type && ! empty( $dep['merged_with'] ) && $dep['merged_with'] === $type ) {
				$types[] = $code;
			}
		}
		return $types;
	}

	try {
		if ( $action === 'save' ) {
			$date   = $data['date']   ?? '';
			$type   = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
			$data_type = $type;
			if ( ! $is_camp ) {
				$merge_with = ! empty( $dept_by_code[ $type ]['merged_with'] ) ? $dept_by_code[ $type ]['merged_with'] : null;
				$data_type = ( $merge_with && isset( $dept_by_code[ $merge_with ] ) ) ? $merge_with : $type;
			}
			$school = $data['school'] ?? null;
			$dept   = $data['dept']   ?? null;
			$is_school      = ! empty( $data['is_school'] );
			$day_num        = isset( $data['day_num'] ) ? (int) $data['day_num'] : 0;
			$is_cycle_start = ! empty( $data['is_cycle_start'] ) ? 1 : 0;
			$iterate_number = ! empty( $data['iterate_number'] ) ? 1 : 0;

			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'Неверная дата', 'meal-menu' ) ) );
			}

			if ( ! $is_school ) {
				if ( $is_camp ) {
					$db->save_camp_calendar_day( $date, null, $school, $dept, $data_type, 0, $iterate_number );
				} else {
					$db->save_calendar_day( $date, null, $school, $dept, $data_type, 0, $iterate_number );
				}
				wp_send_json( array( 'ok' => true, 'template_id' => null, 'day_number' => null, 'iterate_number' => $iterate_number ) );
			}

			if ( $day_num < 1 ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'Укажите день меню', 'meal-menu' ) ) );
			}

			$templates = $is_camp ? $db->get_camp_templates_ordered( $data_type ) : $db->get_templates_ordered( $data_type );
			if ( ! isset( $templates[ $day_num ] ) ) {
				wp_send_json( array( 'ok' => false, 'error' => sprintf( __( 'Шаблон дня %d не найден для типа %s', 'meal-menu' ), $day_num, $data_type ) ) );
			}
			$tpl_id = $templates[ $day_num ];
			if ( $is_camp ) {
				$db->save_camp_calendar_day( $date, $tpl_id, $school, $dept, $data_type, $is_cycle_start, $iterate_number );
			} else {
				$db->save_calendar_day( $date, $tpl_id, $school, $dept, $data_type, $is_cycle_start, $iterate_number );
			}

			$xls_path = null;
			$merge_types = $is_camp ? array( $type ) : get_merge_types( $type, $dept_by_code );
			foreach ( $merge_types as $mt ) {
				$should_publish = $is_camp
					? ! empty( $dept_by_code[ $mt ]['camp_publish_xlsx'] ?? null )
					: ! empty( $dept_by_code[ $mt ]['publish_xlsx'] );
				if ( $should_publish && class_exists( '\Meal_Menu\Excel_Daily' ) ) {
					\Meal_Menu\Excel_Daily::generate( $date, $mt, $is_camp );
				}
			}

			$entry = $is_camp ? $db->get_camp_calendar_day( $date, $data_type ) : $db->get_calendar_day( $date, $data_type );
			wp_send_json( array(
				'ok'          => true,
				'template_id' => $tpl_id,
				'day_number'  => $day_num,
				'label'       => $entry['template_label'] ?? "День {$day_num}",
				'xls'         => null,
			) );

	} elseif ( $action === 'delete' ) {
		$date = $data['date'] ?? '';
		$type = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверная дата', 'meal-menu' ) ) );
		}
		$data_type = $type;
		if ( ! $is_camp ) {
			$merge_with = ! empty( $dept_by_code[ $type ]['merged_with'] ) ? $dept_by_code[ $type ]['merged_with'] : null;
			$data_type  = ( $merge_with && isset( $dept_by_code[ $merge_with ] ) ) ? $merge_with : $type;
		}
		if ( $is_camp ) {
			$db->delete_camp_calendar_day( $date, $data_type );
		} else {
			$db->delete_calendar_day( $date, $data_type );
		}
		wp_send_json( array( 'ok' => true ) );

	} elseif ( $action === 'apply_cycle' ) {
		$date      = $data['date']      ?? '';
		$type      = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$start_day = isset( $data['start_day'] ) ? (int) $data['start_day'] : 1;
		$school    = $data['school']   ?? null;
		$dept      = $data['dept']     ?? null;
		$end_date  = $data['end_date'] ?? null;
		$overwrite = ! empty( $data['overwrite'] );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверная дата', 'meal-menu' ) ) );
		}
		if ( $end_date !== null && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			$end_date = null;
		}

		$data_type = $type;
		if ( ! $is_camp ) {
			$merge_with = ! empty( $dept_by_code[ $type ]['merged_with'] ) ? $dept_by_code[ $type ]['merged_with'] : null;
			$data_type  = ( $merge_with && isset( $dept_by_code[ $merge_with ] ) ) ? $merge_with : $type;
		}
		if ( $is_camp ) {
			$camp_dept = $dept_by_code['sm'] ?? array();
			$workdays  = array_map( 'intval', explode( ',', $camp_dept['camp_workdays'] ?? '1,2,3,4,5' ) );
			$camp_end  = $camp_dept['camp_end_date'] ?? '';
			if ( $camp_end && ( $end_date === null || $end_date > $camp_end ) ) {
				$end_date = $camp_end;
			}
			$count     = $db->assign_camp_cycle( $date, $start_day, $data_type, $school, $dept, $end_date, $workdays, $overwrite );
		} else {
			$workdays = isset( $dept_by_code[ $data_type ] ) ? $db->get_workdays( $data_type ) : array( 1, 2, 3, 4, 5 );
			$count    = $db->assign_cycle( $date, $start_day, $data_type, $school, $dept, $end_date, $workdays, $overwrite );
		}
		wp_send_json( array( 'ok' => true, 'count' => $count ) );

	} elseif ( $action === 'bulk_save' ) {
		$type = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$days = is_array( $data['days'] ?? null ) ? $data['days'] : array();
		if ( $is_camp ) {
			$db->bulk_save_camp_calendar( $days, $type );
		} else {
			$db->bulk_save_calendar( $days, $type );
		}
		wp_send_json( array( 'ok' => true, 'saved' => count( $days ) ) );

	} elseif ( $action === 'copy_month' ) {
		$type   = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$source = in_array( $data['source'] ?? '', $valid_types, true ) ? $data['source'] : '';
		$year   = isset( $data['year'] )  ? (int) $data['year']  : (int) current_time( 'Y' );
		$month  = isset( $data['month'] ) ? (int) $data['month'] : (int) current_time( 'n' );

		if ( ! $source || $source === $type ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверное отделение-источник', 'meal-menu' ) ) );
		}

		$data_type = $type;
		if ( ! $is_camp ) {
			$merge_with = ! empty( $dept_by_code[ $type ]['merged_with'] ) ? $dept_by_code[ $type ]['merged_with'] : null;
			$data_type  = ( $merge_with && isset( $dept_by_code[ $merge_with ] ) ) ? $merge_with : $type;
		}

		$source_cal = $is_camp
			? $db->get_camp_calendar_month( $year, $month, $source )
			: $db->get_calendar_month( $year, $month, $source );
		if ( empty( $source_cal ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'В отделении-источнике нет данных за этот месяц', 'meal-menu' ) ) );
		}

		$school    = null;
		$dept_name = null;

		$templates = $is_camp ? $db->get_camp_templates_ordered( $data_type ) : $db->get_templates_ordered( $data_type );
		$count     = 0;

		foreach ( $source_cal as $date_str => $day ) {
			$tpl_id = null;
			$is_cycle_start = 0;
			$iterate_number = 0;

			if ( $day['template_id'] !== null ) {
				$source_tpl = $is_camp
					? $db->get_camp_template( (int) $day['template_id'] )
					: $db->get_template( (int) $day['template_id'] );
				if ( ! $source_tpl ) continue;
				$source_dn = (int) $source_tpl['day_number'];
				if ( isset( $templates[ $source_dn ] ) ) {
					$tpl_id = $templates[ $source_dn ];
				} else {
					continue;
				}
				$is_cycle_start = (int) $day['is_cycle_start'];
				$iterate_number = (int) $day['iterate_number'];
			} else {
				$iterate_number = (int) $day['iterate_number'];
			}

			if ( $is_camp ) {
				$db->save_camp_calendar_day( $date_str, $tpl_id, $school, $dept_name, $data_type, $is_cycle_start, $iterate_number );
			} else {
				$db->save_calendar_day( $date_str, $tpl_id, $school, $dept_name, $data_type, $is_cycle_start, $iterate_number );
			}
			$count++;
		}

		wp_send_json( array( 'ok' => true, 'count' => $count ) );

	} elseif ( $action === 'recalc_period' ) {
		$type   = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$data_type = $type;
		if ( ! $is_camp ) {
			$merge_with = ! empty( $dept_by_code[ $type ]['merged_with'] ) ? $dept_by_code[ $type ]['merged_with'] : null;
			$data_type  = ( $merge_with && isset( $dept_by_code[ $merge_with ] ) ) ? $merge_with : $type;
		}
		$dept   = $dept_by_code[ $data_type ] ?? null;
		$school = $data['school'] ?? null;
		$dept_name = $data['dept'] ?? null;

		$year   = isset( $data['year'] )  ? (int) $data['year']  : null;
		$month  = isset( $data['month'] ) ? (int) $data['month'] : null;

		$templates = $is_camp ? $db->get_camp_templates_ordered( $data_type ) : $db->get_templates_ordered( $data_type );
		$cycle_len = count( $templates );

		if ( $cycle_len === 0 ) {
			wp_send_json( array( 'ok' => false, 'error' => sprintf( __( 'Нет шаблонов для типа %s', 'meal-menu' ), $data_type ) ) );
		}

		$keys = array_keys( $templates );

		if ( $is_camp ) {
			$camp_dept_info = $dept_by_code['sm'] ?? array();
			$workdays       = array_map( 'intval', explode( ',', $camp_dept_info['camp_workdays'] ?? '1,2,3,4,5' ) );
			$camp_start     = $camp_dept_info['camp_start_date'] ?? '';
			$camp_end       = $camp_dept_info['camp_end_date'] ?? '';

			if ( $year && $month ) {
				$month_from = sprintf( '%04d-%02d-01', $year, $month );
				$month_to   = gmdate( 'Y-m-t', strtotime( $month_from ) );
				$from_date  = $camp_start && $camp_start > $month_from ? $camp_start : $month_from;
				$to_date    = $camp_end   && $camp_end   < $month_to   ? $camp_end   : $month_to;
			} else {
				$from_date = $camp_start ?: sprintf( '%04d-06-01', current_time( 'Y' ) );
				$to_date   = $camp_end   ?: sprintf( '%04d-08-31', current_time( 'Y' ) );
			}

			$start_idx = 0;
			$existing_range = $db->get_camp_calendar_range( $from_date, $to_date, $data_type );

			$cur  = new \DateTime( $from_date );
			$end  = new \DateTime( $to_date );
			$idx  = $start_idx;
			$days = array();

			while ( $cur <= $end ) {
				$date_str   = $cur->format( 'Y-m-d' );
				$wday       = (int) $cur->format( 'N' );
				$is_scheduled = in_array( $wday, $workdays, true );
				$rec        = $existing_range[ $date_str ] ?? null;
				$is_user_holiday = $rec && $rec['template_id'] === null && !$rec['iterate_number'];
				$is_user_workday = $rec && $rec['template_id'] === null && $rec['iterate_number'];
				$has_menu   = $rec && $rec['template_id'] !== null;

				if ( $is_scheduled && ! $is_user_holiday ) {
					if ( ! $has_menu ) {
						$day_num = $keys[ $idx % $cycle_len ];
						$days[] = array(
							'date'           => $date_str,
							'day_num'        => $day_num,
							'school'         => $school,
							'dept'           => $dept_name,
							'is_cycle_start' => $idx === $start_idx ? 1 : 0,
						);
					}
					$idx++;
				} elseif ( $is_user_workday ) {
					if ( ! $has_menu ) {
						$day_num = $keys[ $idx % $cycle_len ];
						$days[] = array(
							'date'           => $date_str,
							'day_num'        => $day_num,
							'school'         => $school,
							'dept'           => $dept_name,
							'is_cycle_start' => $idx === $start_idx ? 1 : 0,
						);
					}
					$idx++;
				}
				$cur->modify( '+1 day' );
			}

			if ( ! empty( $days ) ) {
				$db->bulk_save_camp_calendar( $days, $data_type );
			}

			wp_send_json( array( 'ok' => true, 'count' => count( $days ) ) );
		}

		$workdays = $dept ? $db->get_workdays( $data_type ) : array( 1, 2, 3, 4, 5 );

		$ay_settings    = $db->get_academic_year_settings();
		$reset_after_vac = (bool) $ay_settings['reset_cycle_after_vacation'];

		if ( $year && $month ) {
			$from_date = sprintf( '%04d-%02d-01', $year, $month );
			$to_date   = gmdate( 'Y-m-t', strtotime( $from_date ) );
			$vacation_days = $db->get_vacation_days_for_range( $from_date, $to_date );
		} else {
			$period = $db->get_current_period( $data_type );
			$from_date = $period['from'];
			$to_date   = $period['to'];
			$vacation_days = $db->get_vacation_days_for_range( $from_date, $to_date );
		}

		$c   = $db->get_table_name( 'calendar' );
		$t   = $db->get_table_name( 'templates' );
		global $wpdb;
		$last_before  = $wpdb->get_row( $wpdb->prepare(
			"SELECT t.day_number FROM $c c
			 LEFT JOIN $t t ON t.id = c.template_id
			 WHERE c.date < %s AND c.school_type = %s AND c.template_id IS NOT NULL
			 ORDER BY c.date DESC LIMIT 1",
			$from_date, $data_type
		), ARRAY_A );

		$start_idx = 0;
		if ( $last_before && $last_before['day_number'] && ! $reset_after_vac ) {
			$last_day_num = (int) $last_before['day_number'];
			$pos = array_search( $last_day_num, $keys, true );
			if ( $pos !== false ) {
				$start_idx = ( $pos + 1 ) % $cycle_len;
			}
		}

		$existing_range = $db->get_calendar_range( $from_date, $to_date, $data_type );

		$cur  = new \DateTime( $from_date );
		$end  = new \DateTime( $to_date );
		$idx  = $start_idx;
		$days = array();

		while ( $cur <= $end ) {
			$date_str   = $cur->format( 'Y-m-d' );
			$wday       = (int) $cur->format( 'N' );
			$is_scheduled = in_array( $wday, $workdays, true );
			$is_vac     = isset( $vacation_days[ $date_str ] );
			$rec        = $existing_range[ $date_str ] ?? null;
			$is_user_holiday = $rec && $rec['template_id'] === null && !$rec['iterate_number'];
			$is_user_workday = $rec && $rec['template_id'] === null && $rec['iterate_number'];
			$has_menu   = $rec && $rec['template_id'] !== null;

			if ( ! $is_vac && $is_scheduled && ! $is_user_holiday ) {
				if ( ! $has_menu ) {
					$day_num = $keys[ $idx % $cycle_len ];
					$days[] = array(
						'date'           => $date_str,
						'day_num'        => $day_num,
						'school'         => $school,
						'dept'           => $dept_name,
						'is_cycle_start' => $idx === $start_idx ? 1 : 0,
					);
				}
				$idx++;
			} elseif ( ! $is_vac && $is_user_workday ) {
				if ( ! $has_menu ) {
					$day_num = $keys[ $idx % $cycle_len ];
					$days[] = array(
						'date'           => $date_str,
						'day_num'        => $day_num,
						'school'         => $school,
						'dept'           => $dept_name,
						'is_cycle_start' => $idx === $start_idx ? 1 : 0,
					);
				}
				$idx++;
			}
			$cur->modify( '+1 day' );
		}

		if ( ! empty( $days ) ) {
			$db->bulk_save_calendar( $days, $data_type );
		}

		wp_send_json( array( 'ok' => true, 'count' => count( $days ) ) );

	} elseif ( $action === 'generate_files' ) {
		$type  = in_array( $data['type'] ?? '', $valid_types, true ) ? $data['type'] : 'sm';
		$year  = isset( $data['year'] )  ? (int) $data['year']  : (int) current_time( 'Y' );
		$month = isset( $data['month'] ) ? (int) $data['month'] : (int) current_time( 'n' );

		$gen_types = $is_camp ? array( $type ) : get_merge_types( $type, $dept_by_code );

		$generated = array();
		$days_in_month = (int) ( new \DateTimeImmutable( "$year-$month-01" ) )->format( 't' );
		foreach ( $gen_types as $gt ) {
			if ( $is_camp ) {
				$should_publish = ! empty( $dept_by_code[ $gt ]['camp_publish_xlsx'] ?? null );
			} else {
				$should_publish = ! empty( $dept_by_code[ $gt ]['publish_xlsx'] );
			}
			if ( ! $should_publish ) {
				continue;
			}
			for ( $d = 1; $d <= $days_in_month; $d++ ) {
				$date_str = sprintf( '%04d-%02d-%02d', $year, $month, $d );
				if ( class_exists( '\Meal_Menu\Excel_Daily' ) ) {
					$path = \Meal_Menu\Excel_Daily::generate( $date_str, $gt, $is_camp );
					if ( $path ) {
						$generated[] = basename( $path );
					}
				}
			}
		}
		$kp_type = $type;
		if ( ! $is_camp && ! empty( $dept_by_code[ $kp_type ]['merged_with'] ) ) {
			$kp_type = $dept_by_code[ $kp_type ]['merged_with'];
		}
		if ( ! $db->is_kindergarten() && class_exists( '\Meal_Menu\Excel_KP' ) ) {
			\Meal_Menu\Excel_KP::generate( $year, $kp_type, $is_camp );
		}
		if ( ! $db->is_kindergarten() && class_exists( '\Meal_Menu\Excel_TM' ) ) {
			$_up = wp_upload_dir();
			$_meal_dir = $_up['basedir'] . '/meal-menu';
			foreach ( $gen_types as $gt ) {
				$tm_path = $_meal_dir . "/tm{$year}-{$gt}.xlsx";
				if ( ! file_exists( $tm_path ) ) {
					\Meal_Menu\Excel_TM::generate( $gt, $year, $is_camp );
				}
			}
		}
		set_transient( 'meal_menu_gen_notice', sprintf(
			__( 'Создано файлов: %d', 'meal-menu' ),
			count( $generated )
		), 30 );
		wp_send_json( array( 'ok' => true, 'count' => count( $generated ), 'files' => $generated ) );

	} else {
		wp_send_json( array( 'ok' => false, 'error' => __( 'Неизвестное действие', 'meal-menu' ) ) );
	}
} catch ( \Exception $e ) {
	wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
}
