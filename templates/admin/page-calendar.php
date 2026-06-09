<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$dept_map      = array();
foreach ( $enabled_depts as $dep ) {
	$dept_map[ $dep['code'] ] = $dep;
}
$merged_targets = array();
foreach ( $enabled_depts as $dep ) {
	if ( ! empty( $dep['merged_with'] ) && isset( $dept_map[ $dep['merged_with'] ] ) ) {
		$merged_targets[ $dep['merged_with'] ] = true;
	}
}
$valid_types   = array();
$type_labels   = array();
foreach ( $enabled_depts as $dep ) {
	if ( isset( $merged_targets[ $dep['code'] ] ) ) {
		continue;
	}
	$valid_types[] = $dep['code'];
	$type_labels[ $dep['code'] ] = ! empty( $dep['dept_name'] ) ? $dep['dept_name'] : $dep['label'];
}

$type    = isset( $_GET['type'] ) && in_array( $_GET['type'], $valid_types, true ) ? $_GET['type'] : ( $valid_types[0] ?? 'sm' );
$cur_dept = $dept_map[ $type ] ?? null;
$org_name = $db->get_org_name();

$merge_with = $cur_dept && ! empty( $cur_dept['merged_with'] ) && isset( $dept_map[ $cur_dept['merged_with'] ] ) ? $cur_dept['merged_with'] : null;
$merge_dept = $merge_with ? $dept_map[ $merge_with ] : null;
if ( $merge_with ) {
	$merge_label = ! empty( $merge_dept['dept_name'] ) ? $merge_dept['dept_name'] : $merge_dept['label'];
	$cur_label   = ! empty( $cur_dept['dept_name'] ) ? $cur_dept['dept_name'] : $cur_dept['label'];
	$type_labels[ $type ] = $cur_label . ' + ' . $merge_label;
}

$dept_name = $cur_dept['dept_name'] ?? '';

$today = new \DateTime();
$year  = (int) ( $_GET['y'] ?? $today->format( 'Y' ) );
$month = (int) ( $_GET['m'] ?? $today->format( 'n' ) );
$year  = max( 2020, min( 2035, $year ) );
$month = max( 1, min( 12, $month ) );

$data_type   = $merge_with ? $merge_with : $type;
$cal_data    = $db->get_calendar_month( $year, $month, $data_type );
$first_day   = new \DateTime( sprintf( '%04d-%02d-01', $year, $month ) );
$last_day    = (int) $first_day->format( 't' );
$start_wday  = (int) $first_day->format( 'N' );

$prev_dt = clone $first_day;
$prev_dt->modify( '-1 month' );
$next_dt = clone $first_day;
$next_dt->modify( '+1 month' );

$prev_cal_data     = $db->get_calendar_month( (int) $prev_dt->format( 'Y' ), (int) $prev_dt->format( 'n' ), $data_type );
$prev_last_day     = (int) $prev_dt->format( 't' );
$prev_from         = sprintf( '%04d-%02d-%02d', (int) $prev_dt->format( 'Y' ), (int) $prev_dt->format( 'n' ), $prev_last_day - 6 );
$prev_to           = sprintf( '%04d-%02d-%02d', (int) $prev_dt->format( 'Y' ), (int) $prev_dt->format( 'n' ), $prev_last_day );
$prev_vacations    = $db->get_vacation_days_for_range( $prev_from, $prev_to );

$next_cal_data     = $db->get_calendar_month( (int) $next_dt->format( 'Y' ), (int) $next_dt->format( 'n' ), $data_type );
$next_from         = sprintf( '%04d-%02d-01', (int) $next_dt->format( 'Y' ), (int) $next_dt->format( 'n' ) );
$next_vacations    = $db->get_vacation_days_for_range( $next_from, sprintf( '%04d-%02d-07', (int) $next_dt->format( 'Y' ), (int) $next_dt->format( 'n' ) ) );

$today_str  = $today->format( 'Y-m-d' );
$month_ru   = array( '', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
	'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь' );
$weekdays   = array( 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' );
$suffix      = $cur_dept ? $cur_dept['file_suffix'] : '';
$merge_suffix = $merge_dept ? $merge_dept['file_suffix'] : '';

$upload_dir = wp_upload_dir();
$files_dir  = $upload_dir['basedir'] . '/meal-menu';
$files_url  = $upload_dir['baseurl'] . '/meal-menu';

$all_suffixes = array( $suffix );
if ( $merge_with && $merge_suffix !== $suffix ) {
	$all_suffixes[] = $merge_suffix;
}
foreach ( $enabled_depts as $dep ) {
	if ( $dep['code'] !== $type && ! empty( $dep['merged_with'] ) && $dep['merged_with'] === $type ) {
		$dep_suffix = $dep['file_suffix'];
		if ( ! in_array( $dep_suffix, $all_suffixes, true ) ) {
			$all_suffixes[] = $dep_suffix;
		}
	}
}

$all_known_suffixes = array();
foreach ( $enabled_depts as $dep ) {
	if ( $dep['file_suffix'] !== '' && ! in_array( $dep['file_suffix'], $all_known_suffixes, true ) ) {
		$all_known_suffixes[] = $dep['file_suffix'];
	}
}

$existing_files = array();
foreach ( $all_suffixes as $sfx ) {
	$f_pattern = $files_dir . '/' . sprintf( '%04d-%02d-*%s.xlsx', $year, $month, $sfx );
	foreach ( glob( $f_pattern ) ?: array() as $f ) {
		$name_no_ext = basename( $f, '.xlsx' );
		if ( $sfx === '' ) {
			foreach ( $all_known_suffixes as $ks ) {
				if ( str_ends_with( $name_no_ext, $ks ) ) { continue 2; }
			}
		}
		$existing_files[ substr( basename( $f ), 0, 10 ) ][] = basename( $f );
	}
}

foreach ( $all_suffixes as $sfx ) {
	$prev_f_pattern = $files_dir . '/' . sprintf( '%04d-%02d-*%s.xlsx', (int) $prev_dt->format( 'Y' ), (int) $prev_dt->format( 'n' ), $sfx );
	foreach ( glob( $prev_f_pattern ) ?: array() as $f ) {
		$name_no_ext = basename( $f, '.xlsx' );
		if ( $sfx === '' ) {
			foreach ( $all_known_suffixes as $ks ) {
				if ( str_ends_with( $name_no_ext, $ks ) ) { continue 2; }
			}
		}
		$existing_files[ substr( basename( $f ), 0, 10 ) ][] = basename( $f );
	}
}
foreach ( $all_suffixes as $sfx ) {
	$next_f_pattern = $files_dir . '/' . sprintf( '%04d-%02d-*%s.xlsx', (int) $next_dt->format( 'Y' ), (int) $next_dt->format( 'n' ), $sfx );
	foreach ( glob( $next_f_pattern ) ?: array() as $f ) {
		$name_no_ext = basename( $f, '.xlsx' );
		if ( $sfx === '' ) {
			foreach ( $all_known_suffixes as $ks ) {
				if ( str_ends_with( $name_no_ext, $ks ) ) { continue 2; }
			}
		}
		$existing_files[ substr( basename( $f ), 0, 10 ) ][] = basename( $f );
	}
}

$cycle_len     = $db->get_cycle_length( $data_type );
$tpls_ordered  = $db->get_templates_ordered( $data_type );
$templates_map = array();
foreach ( $tpls_ordered as $dn => $tid ) {
	$templates_map[ $tid ] = $dn;
}

$tpls_all         = $db->get_templates( $data_type );
$day_num_to_label = array();
foreach ( $tpls_all as $t ) {
	$day_num_to_label[ (int) $t['day_number'] ] = $t['label'];
}
$day_num_keys = array_values( array_keys( $tpls_ordered ) );

$gaps = array();
for ( $i = 0; $i < count( $day_num_keys ) - 1; $i++ ) {
	for ( $g = $day_num_keys[ $i ] + 1; $g < $day_num_keys[ $i + 1 ]; $g++ ) {
		$gaps[] = $g;
	}
}

$source_depts = array();
$month_from   = sprintf( '%04d-%02d-01', $year, $month );
$month_to     = gmdate( 'Y-m-t', strtotime( $month_from ) );
foreach ( $enabled_depts as $dep ) {
	if ( $dep['code'] === $type ) continue;
	if ( ! in_array( $dep['code'], $valid_types, true ) ) continue;
	$dep_cycle = $db->get_cycle_length( $dep['code'] );
	if ( $dep_cycle !== $cycle_len || $cycle_len === 0 ) continue;
	$dep_cal = $db->get_calendar_month( $year, $month, $dep['code'] );
	$has_data = false;
	foreach ( $dep_cal as $day ) {
		if ( $day['template_id'] !== null ) { $has_data = true; break; }
	}
	if ( $has_data ) {
		$source_depts[] = $dep;
	}
}
$sources_json = json_encode( $source_depts, JSON_UNESCAPED_UNICODE );

global $wpdb;
$c = $db->get_table_name( 'calendar' );
$t = $db->get_table_name( 'templates' );
$last_before_row = $wpdb->get_row( $wpdb->prepare(
	"SELECT t.day_number FROM $c c
	 LEFT JOIN $t t ON t.id = c.template_id
	 WHERE c.date < %s AND c.school_type = %s AND c.template_id IS NOT NULL
	 ORDER BY c.date DESC LIMIT 1",
	$first_day->format( 'Y-m-d' ), $data_type
), ARRAY_A );

$default_start_day = $day_num_keys[0] ?? 1;
if ( $last_before_row && $last_before_row['day_number'] ) {
	$last_day_num = (int) $last_before_row['day_number'];
	$pos = array_search( $last_day_num, $day_num_keys, true );
	if ( $pos !== false ) {
		$default_start_day = $day_num_keys[ ( $pos + 1 ) % count( $day_num_keys ) ];
	}
}

$cur_dept_for_wd = $merge_dept ? $merge_dept : $cur_dept;
$cur_workdays = $cur_dept_for_wd ? explode( ',', $cur_dept_for_wd['workdays'] ) : array( '1', '2', '3', '4', '5' );

$cur_dept_for_vac = $merge_dept ? $merge_dept : $cur_dept;
$vacation_days = $db->get_vacation_days_for_range( $month_from, $month_to );
$cur_period    = $db->get_current_period( $data_type, $today_str );

$actual_dates = array();
foreach ( $vacation_days as $d => $info ) {
	if ( ! empty( $info['actual_date'] ) ) {
		$actual_dates[ $info['actual_date'] ] = $info['label'];
	}
}
?>
<?php
$gen_notice = get_transient( 'meal_menu_gen_notice' );
if ( $gen_notice ) {
	delete_transient( 'meal_menu_gen_notice' );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $gen_notice ) . '</p></div>';
}
?>
<div class="wrap meal-menu-wrap cal-layout">
	<div class="cal-main">
		<div class="flex items-center justify-between mb-2">
			<h1 class="page-title" style="border:none;margin:0"><?php _e( 'Календарь меню', 'meal-menu' ); ?></h1>
			<div class="flex gap-2">
				<button type="button" id="btn-recalc" class="btn btn-outline btn-sm"><?php printf( __( 'Заполнить %s', 'meal-menu' ), $month_ru[ $month ] . ' ' . $year ); ?></button>
				<?php if ( ! empty( $cur_dept['publish_xlsx'] ) ): ?>
				<button type="button" id="btn-gen-files" class="btn btn-primary btn-sm"><?php _e( 'Создать файлы', 'meal-menu' ); ?></button>
				<?php endif; ?>
			</div>
		</div>

		<div class="tab-bar">
			<?php foreach ( $type_labels as $t => $label ): ?>
			<a href="admin.php?page=meal-calendar&type=<?php echo esc_attr( $t ); ?>&y=<?php echo $year; ?>&m=<?php echo $month; ?>"
			   class="tab-item<?php echo $type === $t ? ' active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<div class="panel">
			<div class="cycle-info" style="font-size:.78rem;color:var(--wp-muted);padding:6px 0 0">
				<?php if ( $cycle_len === 0 ): ?>
					<span style="color:var(--error)"><?php _e( 'Шаблоны не созданы.', 'meal-menu' ); ?></span>
					<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $type ); ?>"><?php _e( 'Добавить', 'meal-menu' ); ?> →</a>
				<?php elseif ( ! empty( $gaps ) ): ?>
					<?php printf( __( 'Цикл: %d дн. (%d–%d)', 'meal-menu' ), $cycle_len, $day_num_keys[0], end( $day_num_keys ) ); ?>
					· <span style="color:var(--error)"><?php printf( __( 'Пропущены №%s.', 'meal-menu' ), implode( ', №', array_map( 'esc_html', $gaps ) ) ); ?></span>
					<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $type ); ?>"><?php _e( 'Исправить', 'meal-menu' ); ?> →</a>
				<?php else: ?>
					<?php printf( __( 'Цикл: %d дн. (%d–%d)', 'meal-menu' ), $cycle_len, $day_num_keys[0], end( $day_num_keys ) ); ?>
					· <?php printf( __( 'Начало: день %d', 'meal-menu' ), (int) $default_start_day ); ?>
					<?php if ( ! empty( $cur_period['label'] ) ): ?>
					· <?php echo esc_html( $cur_period['label'] ); ?>
					  (<?php echo gmdate( 'd.m', strtotime( $cur_period['from'] ) ); ?> – <?php echo gmdate( 'd.m', strtotime( $cur_period['to'] ) ); ?>)
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="cal-nav">
				<a href="admin.php?page=meal-calendar&type=<?php echo esc_attr( $type ); ?>&y=<?php echo $prev_dt->format( 'Y' ); ?>&m=<?php echo $prev_dt->format( 'n' ); ?>" class="btn btn-dark btn-sm">&larr;</a>
				<h2><?php echo $month_ru[ $month ]; ?> <?php echo $year; ?></h2>
				<a href="admin.php?page=meal-calendar&type=<?php echo esc_attr( $type ); ?>&y=<?php echo $next_dt->format( 'Y' ); ?>&m=<?php echo $next_dt->format( 'n' ); ?>" class="btn btn-dark btn-sm">&rarr;</a>
			</div>

			<div class="cal-grid" id="cal-grid">
				<?php foreach ( $weekdays as $wd ): ?>
					<div class="cal-head"><?php echo $wd; ?></div>
				<?php endforeach; ?>

				<?php for ( $i = 1; $i < $start_wday; $i++ ):
					$prev_d          = $prev_last_day - $start_wday + 1 + $i;
					$prev_date_str   = sprintf( '%04d-%02d-%02d', (int) $prev_dt->format( 'Y' ), (int) $prev_dt->format( 'n' ), $prev_d );
					$prev_entry      = $prev_cal_data[ $prev_date_str ] ?? null;
					$prev_is_vac     = isset( $prev_vacations[ $prev_date_str ] );
					$prev_is_holiday = $prev_is_vac && $prev_vacations[ $prev_date_str ]['is_holiday'];
					$prev_has_file   = isset( $existing_files[ $prev_date_str ] );
					$prev_entry_json = $prev_entry ? json_encode( array(
						'template_id'    => $prev_entry['template_id'],
						'day_number'     => $prev_entry['template_id'] ? ( $templates_map[ (int) $prev_entry['template_id'] ] ?? null ) : null,
						'label'          => $prev_entry['template_label'] ?? null,
						'school'         => $prev_entry['school'] ?? '',
						'dept'           => $prev_entry['dept']   ?? '',
						'is_cycle_start' => (int) ( $prev_entry['is_cycle_start'] ?? 0 ),
						'iterate_number' => (int) ( $prev_entry['iterate_number'] ?? 0 ),
					), JSON_UNESCAPED_UNICODE ) : 'null';
				?>
					<div class="cal-cell cal-ghost" data-date="<?php echo $prev_date_str; ?>">
						<div class="cal-day cal-ghost-day"><?php echo $prev_d; ?></div>
						<?php if ( $prev_is_vac && ! $prev_is_holiday ): ?>
							<div class="cal-ghost-label"><?php _e( 'каникулы', 'meal-menu' ); ?></div>
						<?php elseif ( $prev_is_holiday ): ?>
							<div class="cal-ghost-label"><?php _e( 'выходной', 'meal-menu' ); ?></div>
						<?php elseif ( $prev_entry && $prev_entry['template_id'] ): ?>
							<div class="cal-ghost-label"><?php echo esc_html( $prev_entry['template_label'] ); ?></div>
						<?php elseif ( $prev_entry && $prev_entry['template_id'] === null && ! empty( $prev_entry['iterate_number'] ) ): ?>
							<div class="cal-ghost-label"><?php _e( 'Рабочий день', 'meal-menu' ); ?></div>
						<?php endif; ?>
						<?php if ( $prev_has_file ): ?>
							<?php foreach ( (array) $existing_files[ $prev_date_str ] as $prev_fname ): ?>
							<div class="cal-file-badge cal-ghost-badge"><?php echo esc_html( $prev_fname ); ?></div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				<?php endfor; ?>

				<?php for ( $d = 1; $d <= $last_day; $d++ ):
					$date_str = sprintf( '%04d-%02d-%02d', $year, $month, $d );
					$wday     = (int) ( new \DateTime( $date_str ) )->format( 'N' );
					$entry    = $cal_data[ $date_str ] ?? null;
					$is_today = ( $date_str === $today_str );

					$is_vacation = isset( $vacation_days[ $date_str ] );
					$vac_label   = $is_vacation ? $vacation_days[ $date_str ]['label'] : '';
					$is_holiday  = $is_vacation && $vacation_days[ $date_str ]['is_holiday'];
					$actual_date = $is_vacation && ! empty( $vacation_days[ $date_str ]['actual_date'] ) ? $vacation_days[ $date_str ]['actual_date'] : null;

					$is_user_workday = $entry && $entry['template_id'] === null && ! empty( $entry['iterate_number'] );
					$cls = 'cal-cell';
					if ( $wday >= 6 && ! $is_user_workday ) {
						$cls .= ' weekend';
					}
					if ( $is_user_workday ) {
						$cls .= ' user-workday';
					}
					if ( $is_vacation && ! $is_holiday ) {
						$cls .= ' vacation';
					} elseif ( $is_vacation && $is_holiday ) {
						$cls .= ' holiday';
					} elseif ( $entry && $entry['template_id'] === null && empty( $entry['iterate_number'] ) ) {
						$cls .= ' holiday';
					} elseif ( $entry && $entry['template_id'] ) {
						$cls .= ' has-menu';
					}
					if ( $is_today ) {
						$cls .= ' today';
					}
					if ( $entry && ! empty( $entry['is_cycle_start'] ) ) {
						$cls .= ' cycle-start';
					}

					$entry_has_data = $entry && $entry['template_id'];
					$entry_json = $entry ? json_encode( array(
						'template_id'    => $entry['template_id'],
						'day_number'     => $entry_has_data ? ( $templates_map[ (int) $entry['template_id'] ] ?? null ) : null,
						'label'          => $entry['template_label'] ?? null,
						'school'         => $entry['school'] ?? '',
						'dept'           => $entry['dept']   ?? '',
						'is_cycle_start' => (int) ( $entry['is_cycle_start'] ?? 0 ),
						'iterate_number' => (int) ( $entry['iterate_number'] ?? 0 ),
					), JSON_UNESCAPED_UNICODE ) : 'null';
				?>
				<?php
					$is_actual_holiday = isset( $actual_dates[ $date_str ] );
					if ( $is_actual_holiday ) {
						$cls .= ' actual-holiday';
					}
				?>
				<div class="<?php echo $cls; ?>"
					data-date="<?php echo $date_str; ?>"
					data-entry="<?php echo esc_attr( $entry_json ); ?>"
					<?php echo $is_vacation ? 'data-vacation="' . esc_attr( $vac_label ) . '"' : ''; ?>
					<?php echo isset( $existing_files[ $date_str ] ) ? 'data-has-file="1"' : ''; ?>
					<?php if ( $entry && $entry['template_id'] ): ?>data-day-num="<?php echo (int) ( $templates_map[ (int) $entry['template_id'] ] ?? '' ); ?>"<?php endif; ?>>
					<div class="cal-day"><?php echo $d; ?></div>
					<?php if ( $is_vacation && ! $is_holiday ): ?>
						<div class="cal-vacation" style="font-size:.72rem;color:#7a5c9a;line-height:1.2"><?php _e( 'каникулы', 'meal-menu' ); ?></div>
					<?php elseif ( $is_holiday ): ?>
						<div class="cal-no-school"><?php _e( 'выходной', 'meal-menu' ); ?></div>
					<?php elseif ( $entry && $entry['template_id'] ): ?>
						<div class="cal-label"><?php echo esc_html( $entry['template_label'] ); ?></div>
					<?php elseif ( $entry && $entry['template_id'] === null && empty( $entry['iterate_number'] ) ): ?>
						<div class="cal-no-school"><?php _e( 'выходной', 'meal-menu' ); ?></div>
					<?php elseif ( $entry && $entry['template_id'] === null && ! empty( $entry['iterate_number'] ) ): ?>
						<div class="cal-workday-label"><?php _e( 'Рабочий день', 'meal-menu' ); ?></div>
					<?php endif; ?>
					<?php if ( $is_actual_holiday ): ?>
						<div class="cal-actual-holiday"><?php echo esc_html( $actual_dates[ $date_str ] ); ?></div>
					<?php endif; ?>
					<?php if ( isset( $existing_files[ $date_str ] ) ): ?>
						<?php foreach ( (array) $existing_files[ $date_str ] as $fname ): ?>
						<div class="cal-file-badge"><?php echo esc_html( $fname ); ?></div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<?php endfor; ?>

			<?php
			$end_wday = (int) ( new \DateTime( sprintf( '%04d-%02d-%02d', $year, $month, $last_day ) ) )->format( 'N' );
			for ( $i = $end_wday + 1; $i <= 7; $i++ ):
				$next_d          = $i - $end_wday;
				$next_date_str   = sprintf( '%04d-%02d-%02d', (int) $next_dt->format( 'Y' ), (int) $next_dt->format( 'n' ), $next_d );
				$next_entry      = $next_cal_data[ $next_date_str ] ?? null;
				$next_is_vac     = isset( $next_vacations[ $next_date_str ] );
				$next_is_holiday = $next_is_vac && $next_vacations[ $next_date_str ]['is_holiday'];
				$next_has_file   = isset( $existing_files[ $next_date_str ] );
			?>
				<div class="cal-cell cal-ghost" data-date="<?php echo $next_date_str; ?>">
					<div class="cal-day cal-ghost-day"><?php echo $next_d; ?></div>
					<?php if ( $next_is_vac && ! $next_is_holiday ): ?>
						<div class="cal-ghost-label"><?php _e( 'каникулы', 'meal-menu' ); ?></div>
					<?php elseif ( $next_is_holiday ): ?>
						<div class="cal-ghost-label"><?php _e( 'выходной', 'meal-menu' ); ?></div>
					<?php elseif ( $next_entry && $next_entry['template_id'] ): ?>
						<div class="cal-ghost-label"><?php echo esc_html( $next_entry['template_label'] ); ?></div>
					<?php endif; ?>
					<?php if ( $next_has_file ): ?>
						<?php foreach ( (array) $existing_files[ $next_date_str ] as $next_fname ): ?>
						<div class="cal-file-badge cal-ghost-badge"><?php echo esc_html( $next_fname ); ?></div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			<?php endfor; ?>
			</div>

			<div class="legend" style="margin-top:12px">
				<div class="legend-item"><div class="legend-dot has-menu"></div> <?php _e( 'Меню', 'meal-menu' ); ?></div>
				<div class="legend-item"><div class="legend-dot holiday"></div> <?php _e( 'Выходной', 'meal-menu' ); ?></div>
				<div class="legend-item"><div class="legend-dot" style="background:#c4a8e0"></div> <?php _e( 'Каникулы', 'meal-menu' ); ?></div>
				<div class="legend-item"><div class="legend-dot no-menu"></div> <?php _e( 'Не задан', 'meal-menu' ); ?></div>
			</div>
			<div id="day-popup" style="display:none;position:fixed;z-index:500;background:#fff;border:1px solid var(--wp-border-subtle);border-top:3px solid var(--wp-blue);padding:14px 18px;min-width:200px;max-width:280px;box-shadow:0 4px 16px rgba(0,0,0,.15)">
				<div class="day-popup-title" id="popup-title" style="font-size:.88rem;font-weight:bold;color:var(--wp-text);margin-bottom:4px"></div>
				<div class="day-popup-status" id="popup-status" style="font-size:.82rem;color:var(--wp-muted);margin-bottom:12px"></div>
				<div class="day-popup-actions" id="popup-actions" style="display:flex;flex-direction:column;gap:6px"></div>
			</div>
		</div>
	</div>

	<div class="cal-help-sidebar">
		<div style="background:#faf6f0;border:1px solid #ede4d8;border-radius:6px;padding:14px">
			<div style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--wp-blue);font-weight:bold;margin-bottom:10px"><?php _e( 'Как заполнить месяц', 'meal-menu' ); ?></div>

			<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
				<div style="background:var(--wp-blue);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;margin-top:1px">1</div>
				<div><?php _e( 'Выберите <strong>вкладку отделения</strong> сверху.', 'meal-menu' ); ?></div>
			</div>

			<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
				<div style="background:var(--wp-blue);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;margin-top:1px">2</div>
				<div><?php _e( 'Перейдите на нужный месяц стрелками.', 'meal-menu' ); ?></div>
			</div>

			<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
				<div style="background:var(--wp-blue);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;margin-top:1px">3</div>
				<div><?php _e( 'Отметьте <strong>выходные</strong> и <strong>рабочие дни</strong> кликом по ячейке.', 'meal-menu' ); ?></div>
			</div>

			<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
				<div style="background:var(--wp-blue);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;margin-top:1px">4</div>
				<div><?php _e( 'Кликните на <strong>первый рабочий день</strong>, укажите номер меню и нажмите «Заполнить до конца месяца».', 'meal-menu' ); ?></div>
			</div>

			<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
				<div style="background:var(--wp-blue);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;margin-top:1px">5</div>
				<div><?php _e( 'Нажмите <strong>«Создать файлы»</strong> для генерации .xlsx.', 'meal-menu' ); ?></div>
			</div>

			<div style="display:flex;gap:8px;align-items:flex-start">
				<div style="background:var(--wp-blue);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;margin-top:1px">6</div>
				<div><?php _e( 'Повторите для <strong>других отделений</strong>.', 'meal-menu' ); ?></div>
			</div>

			<div style="margin-top:10px;padding-top:10px;border-top:1px solid #ede4d8">
				<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:6px">
					<div style="color:var(--wp-blue);font-size:.7rem;flex-shrink:0;margin-top:1px">💡</div>
					<div style="color:var(--wp-muted)"><?php _e( 'Если в середине месяца график поменялся — кликните на любой день, измените номер и снова нажмите «Заполнить до конца месяца».', 'meal-menu' ); ?></div>
				</div>
				<div style="display:flex;gap:8px;align-items:flex-start">
					<div style="color:var(--wp-blue);font-size:.7rem;flex-shrink:0;margin-top:1px">💡</div>
					<div style="color:var(--wp-muted)"><?php _e( 'Кнопка «Заполнить [месяц]» заполняет только пустые дни, не меняя уже назначенные.', 'meal-menu' ); ?></div>
				</div>
			</div>

			<?php if ( ! empty( $source_depts ) ): ?>
			<div style="margin-top:10px;padding-top:10px;border-top:1px solid #ede4d8">
				<div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:var(--wp-blue);font-weight:bold;margin-bottom:8px"><?php _e( 'Быстрое копирование', 'meal-menu' ); ?></div>
				<?php foreach ( $source_depts as $src ): ?>
				<button type="button" class="btn btn-outline btn-sm" data-action="copy-from" data-source="<?php echo esc_attr( $src['code'] ); ?>" style="display:block;width:100%;margin-bottom:4px;font-size:.75rem;text-align:left">
					<?php printf( __( 'Копировать из %s', 'meal-menu' ), esc_html( $src['label'] ) ); ?>
				</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<div id="gen-overlay" style="display:none;position:fixed;inset:0;background:rgba(255,255,255,.75);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:12px;font-size:1.1rem;color:var(--wp-text)">
	<div style="width:36px;height:36px;border:4px solid #e0e0e0;border-top-color:var(--wp-blue);border-radius:50%;animation:gen-spin .7s linear infinite"></div>
	<span><?php _e( 'Генерация файлов…', 'meal-menu' ); ?></span>
</div>
<style>
@keyframes gen-spin { to { transform: rotate(360deg); } }
</style>

<script>
(function() {
	var curType      = <?php echo json_encode( $type ); ?>;
	var cycleLen     = <?php echo (int) $cycle_len; ?>;
	var dayNumKeys   = <?php echo json_encode( $day_num_keys ); ?>;
	var dayNumToTplId = <?php echo json_encode( $tpls_ordered, JSON_UNESCAPED_UNICODE ); ?>;
	var dayNumToLabel = <?php echo json_encode( $day_num_to_label, JSON_UNESCAPED_UNICODE ); ?>;
	var curMonth     = <?php echo $month; ?>;
	var curYear      = <?php echo $year; ?>;
	var orgName      = <?php echo json_encode( $org_name ); ?>;
	var deptName     = <?php echo json_encode( $dept_name ); ?>;
	var defaultStartDay = <?php echo (int) $default_start_day; ?>;
	var defaultWorkdays = <?php echo json_encode( array_map( 'intval', $cur_workdays ) ); ?>;
	var publishXlsx  = <?php echo ! empty( $cur_dept_for_wd['publish_xlsx'] ) ? 'true' : 'false'; ?>;
	var sourceDepts  = <?php echo $sources_json; ?>;
	var vacationDays = <?php echo json_encode( array_keys( $vacation_days ) ); ?>;
	var holidayDates = <?php echo json_encode( array_keys( array_filter( $vacation_days, function( $v ) { return $v['is_holiday']; } ) ) ); ?>;
	var ajaxUrl      = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce        = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';

	function getSortedCells() {
		return Array.from(document.querySelectorAll('.cal-cell[data-date]:not(.empty)'))
			.sort(function(a, b) { return a.dataset.date.localeCompare(b.dataset.date); });
	}
	function getCellWday(cell) {
		var d = new Date(cell.dataset.date + 'T00:00:00');
		var w = d.getDay();
		return w === 0 ? 7 : w;
	}
	function getCellEntry(cell) {
		return cell.dataset.entry !== 'null' ? JSON.parse(cell.dataset.entry) : null;
	}
	function escHtml(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
	}

	function apiPost(payload, callback) {
		payload.nonce = nonce;
		jQuery.post(ajaxUrl + '?action=meal_save_day', JSON.stringify(payload), function(r) {
			try { callback(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { callback({ ok: false, error: 'Ошибка ответа' }); }
		}).fail(function() { callback({ ok: false, error: 'Сетевая ошибка' }); });
	}

	function updateCell(cell, entry) {
		cell.dataset.entry = JSON.stringify(entry);
		cell.classList.remove('has-menu', 'holiday', 'weekend', 'user-workday', 'cycle-start');
		if (entry && entry.template_id) {
			cell.classList.add('has-menu');
			if (entry.is_cycle_start) cell.classList.add('cycle-start');
		} else if (entry && entry.template_id === null && entry.iterate_number) {
			cell.classList.add('user-workday');
		} else if (entry && entry.template_id === null && !entry.iterate_number) {
			cell.classList.add('holiday');
		}
		var dayEl = cell.querySelector('.cal-day');
		var labelEl = cell.querySelector('.cal-label');
		var wdLabelEl = cell.querySelector('.cal-workday-label');
		var noSchoolEl = cell.querySelector('.cal-no-school');
		if (entry && entry.template_id) {
			if (!labelEl) {
				labelEl = document.createElement('div');
				labelEl.className = 'cal-label';
				cell.appendChild(labelEl);
			}
			labelEl.textContent = entry.label;
			if (noSchoolEl) noSchoolEl.remove();
			if (wdLabelEl) wdLabelEl.remove();
		} else if (entry && entry.template_id === null && entry.iterate_number) {
			if (!wdLabelEl) {
				wdLabelEl = document.createElement('div');
				wdLabelEl.className = 'cal-workday-label';
				cell.appendChild(wdLabelEl);
			}
			wdLabelEl.textContent = 'Рабочий день';
			if (labelEl) labelEl.remove();
			if (noSchoolEl) noSchoolEl.remove();
		} else if (entry && entry.template_id === null && !entry.iterate_number) {
			if (!noSchoolEl) {
				noSchoolEl = document.createElement('div');
				noSchoolEl.className = 'cal-no-school';
				cell.appendChild(noSchoolEl);
			}
			noSchoolEl.textContent = 'выходной';
			if (labelEl) labelEl.remove();
			if (wdLabelEl) wdLabelEl.remove();
		} else {
			if (labelEl) labelEl.remove();
			if (noSchoolEl) noSchoolEl.remove();
			if (wdLabelEl) wdLabelEl.remove();
		}
	}

	// ── Попап дня ───────────────────────────────────────────
	var popup = document.getElementById('day-popup');
	var popupTitle = document.getElementById('popup-title');
	var popupStatus = document.getElementById('popup-status');
	var popupActions = document.getElementById('popup-actions');

	function getLastDayOfMonth(dateStr) {
		var p = dateStr.split('-');
		var y = parseInt(p[0]), m = parseInt(p[1]);
		var d = new Date(y, m, 0).getDate();
		return p[0] + '-' + p[1] + '-' + ('0' + d).slice(-2);
	}

	function nextDayAfter(dateStr) {
		var d = new Date(dateStr + 'T00:00:00');
		d.setDate(d.getDate() + 1);
		return d.getFullYear() + '-' +
			('0' + (d.getMonth() + 1)).slice(-2) + '-' +
			('0' + d.getDate()).slice(-2);
	}

	function nextDayNum(num, len) {
		return (num % len) + 1;
	}

	document.getElementById('cal-grid').addEventListener('click', function(e) {
		var cell = e.target.closest('.cal-cell');
		if (!cell || cell.classList.contains('empty') || cell.dataset.vacation) return;
		document.querySelectorAll('.cal-cell.selected').forEach(function(c) { c.classList.remove('selected'); });
		cell.classList.add('selected');
		var date = cell.dataset.date;
		var entry = getCellEntry(cell);
		var hasEntry = entry && entry.template_id;
		var wd = getCellWday(cell);
		var isWorkday = defaultWorkdays.indexOf(wd) >= 0;
		var isVacDay = vacationDays.indexOf(date) >= 0;
		var cellDayNum = cell.dataset.dayNum ? parseInt(cell.dataset.dayNum, 10) : null;
		var isEffectiveWorkday = isWorkday || (entry && entry.iterate_number);

		popupTitle.textContent = date;
		var statusHtml = '';
		if (isVacDay) {
			var isHol = holidayDates.indexOf(date) >= 0;
			if (isHol) {
				statusHtml = '<span style="color:var(--wp-muted)">Выходной день</span>';
			} else {
				statusHtml = '<span style="color:#7a5c9a">Каникулы</span>';
			}
		} else if (!isEffectiveWorkday) statusHtml = '<span style="color:var(--wp-muted)">Выходной день</span>';
		popupStatus.innerHTML = statusHtml;


		var isHoliday = entry && entry.template_id === null && !entry.iterate_number;
		var hasMenu = entry && entry.template_id !== null;
		var iterateChecked = entry && entry.iterate_number ? ' checked' : '';
		var html = '';

		if (isEffectiveWorkday && !isVacDay && cycleLen > 0) {
			var currentDayNum = cellDayNum;
			html += '<div style="font-size:.8rem;color:var(--wp-muted);margin-bottom:4px">День меню №:</div>';
			html += '<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px" id="day-num-selector">';
			dayNumKeys.forEach(function(dn) {
				var active = currentDayNum !== null && currentDayNum === dn;
				html += '<button class="btn btn-sm ' + (active ? 'btn-primary' : 'btn-outline') + '" data-action="select-day" data-day="' + dn + '" style="padding:2px 8px;font-size:.78rem">' + dn + '</button>';
			});
			html += '</div>';
			var startDay = currentDayNum || dayNumKeys[0];
			html += '<button class="btn btn-primary btn-sm" id="btn-fill-month" data-start-day="' + startDay + '">Заполнить до конца месяца</button>';
		}

		if (!isVacDay && !isHoliday) {
			if (isEffectiveWorkday && !hasMenu) {
				html += '<button class="btn btn-outline btn-sm" data-action="toggle-holiday" style="margin-top:6px">Выходной</button>';
			}
			if (!isEffectiveWorkday) {
				html += '<button class="btn btn-outline btn-sm" data-action="make-workday" style="margin-top:6px">Сделать рабочим днём</button>';
			}
		}

		if (isHoliday) {
			html += '<button class="btn btn-outline btn-sm" data-action="delete" style="margin-top:6px">Убрать выходной</button>';
			html += '<label style="display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--wp-muted);margin-top:6px;cursor:pointer">';
			html += '<input type="checkbox" id="chk-iterate"' + iterateChecked + '> Учитывать в нумерации';
			html += '</label>';
		}

		if (hasMenu) {
			html += '<button class="btn btn-danger btn-sm" data-action="delete" style="margin-top:6px">Очистить</button>';
		}

		popupActions.innerHTML = html;

		var r = cell.getBoundingClientRect();
		if (window.innerWidth < 600) {
			popup.style.left = '12px';
			popup.style.right = '12px';
			popup.style.top = Math.min(r.top + window.scrollY, window.innerHeight - 300 + window.scrollY) + 'px';
			popup.style.maxWidth = 'none';
			popup.style.width = 'auto';
		} else {
			var left = r.left + window.scrollX;
			var top = r.bottom + window.scrollY + 4;
			if (top + 300 > window.innerHeight) top = r.top + window.scrollY - 300;
			popup.style.left = Math.min(left, window.innerWidth - 300) + 'px';
			popup.style.top = top + 'px';
			popup.style.right = 'auto';
			popup.style.maxWidth = '280px';
			popup.style.width = '';
		}
		popup.style.display = 'block';
	});

	popupActions.addEventListener('click', function(e) {
		var btn = e.target.closest('button[data-action]');
		var fillBtn = e.target.closest('#btn-fill-month');

		if (fillBtn) {
			var cell = document.querySelector('.cal-cell[data-date="' + popupTitle.textContent + '"]');
			if (!cell) return;
			var startDay = parseInt(fillBtn.dataset.startDay);
			var endDate = getLastDayOfMonth(popupTitle.textContent);
			apiPost({ action: 'save', date: popupTitle.textContent, type: curType, is_school: true, day_num: startDay, is_cycle_start: 1 }, function(r1) {
				if (r1.ok) {
					var nextDate = nextDayAfter(popupTitle.textContent);
					var nextDay = nextDayNum(startDay, cycleLen);
					apiPost({ action: 'apply_cycle', date: nextDate, type: curType, start_day: nextDay, end_date: endDate, overwrite: true }, function(r2) {
						if (r2.ok) { location.reload(); }
					});
				}
			});
			return;
		}

		if (!btn) return;
		var cell = document.querySelector('.cal-cell[data-date="' + popupTitle.textContent + '"]');
		if (!cell) return;
		var action = btn.dataset.action;

		if (action === 'select-day') {
			var day = parseInt(btn.dataset.day);
			document.querySelectorAll('#day-num-selector button').forEach(function(b) {
				b.className = 'btn btn-sm btn-outline';
				b.style.cssText = 'padding:2px 8px;font-size:.78rem';
			});
			btn.className = 'btn btn-sm btn-primary';
			btn.style.cssText = 'padding:2px 8px;font-size:.78rem';
			var fillBtnEl = document.getElementById('btn-fill-month');
			if (fillBtnEl) {
				fillBtnEl.dataset.startDay = day;
			}
		} else if (action === 'toggle-holiday') {
			apiPost({ action: 'save', date: popupTitle.textContent, type: curType, is_school: false }, function(r) {
				if (r.ok) {
					var entry = { template_id: null, day_number: null, label: null, is_cycle_start: 0, iterate_number: 0 };
					updateCell(cell, entry);
					cell.click();
				}
			});
		} else if (action === 'make-workday') {
			apiPost({ action: 'save', date: popupTitle.textContent, type: curType, is_school: false, iterate_number: 1 }, function(r) {
				if (r.ok) {
					var entry = { template_id: null, day_number: null, label: null, is_cycle_start: 0, iterate_number: 1 };
					updateCell(cell, entry);
					cell.click();
				}
			});
		} else if (action === 'delete') {
			apiPost({ action: 'delete', date: popupTitle.textContent, type: curType }, function(r) {
				if (r.ok) { updateCell(cell, null); cell.click(); }
			});
		}
	});

	document.addEventListener('change', function(e) {
		if (e.target.id === 'chk-iterate') {
			var iterate = e.target.checked ? 1 : 0;
			apiPost({ action: 'save', date: popupTitle.textContent, type: curType, is_school: false, iterate_number: iterate }, function(r) {
				if (r.ok) {
					var c = document.querySelector('.cal-cell[data-date="' + popupTitle.textContent + '"]');
					if (c) {
						var ent = getCellEntry(c) || { template_id: null };
						ent.iterate_number = r.iterate_number;
						c.dataset.entry = JSON.stringify(ent);
					}
				}
			});
		}
	});

	document.addEventListener('click', function(e) {
		if (!popup.contains(e.target) && !e.target.closest('.cal-cell')) {
			popup.style.display = 'none';
		}
	});

	// ── Заполнить месяц ────────────────────────────────────
	var monthRu = <?php echo json_encode( $month_ru[ $month ] ); ?>;
	document.getElementById('btn-recalc').addEventListener('click', function() {
		apiPost({ action: 'recalc_period', type: curType, year: curYear, month: curMonth }, function(r) {
			if (r.ok) { location.reload(); }
			else { alert(r.error || 'Ошибка при заполнении месяца'); }
		});
	});

	// ── Копировать из другого отделения ──────────────────────
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('[data-action="copy-from"]');
		if (!btn) return;
		var source = btn.dataset.source;
		var srcLabel = '';
		sourceDepts.forEach(function(d) { if (d.code === source) srcLabel = d.label; });
		if (!confirm('Скопировать все дни месяца из «' + srcLabel + '» в текущее отделение?')) return;
		apiPost({ action: 'copy_month', type: curType, source: source, year: curYear, month: curMonth }, function(r) {
			if (r.ok) { location.reload(); }
			else { alert(r.error || 'Ошибка копирования'); }
		});
	});

	// ── Создать файлы ────────────────────────────────────────
	if (document.getElementById('btn-gen-files')) {
		document.getElementById('btn-gen-files').addEventListener('click', function() {
			document.getElementById('gen-overlay').style.display = 'flex';
			apiPost({ action: 'generate_files', type: curType, year: curYear, month: curMonth }, function(r) {
				if (r.ok) { location.reload(); }
			});
		});
	}
})();
</script>
