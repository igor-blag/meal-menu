<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );
$type_labels   = array_combine( array_column( $enabled_depts, 'code' ), array_column( $enabled_depts, 'label_short' ) );
$dept_map      = array();
foreach ( $enabled_depts as $dep ) {
	$dept_map[ $dep['code'] ] = $dep;
}

$type    = isset( $_GET['type'] ) && in_array( $_GET['type'], $valid_types, true ) ? $_GET['type'] : ( $valid_types[0] ?? 'sm' );
$cur_dept = $dept_map[ $type ] ?? null;
$org_name = $db->get_org_name();
$dept_name = $cur_dept['dept_name'] ?? '';

$today = new \DateTime();
$year  = (int) ( $_GET['y'] ?? $today->format( 'Y' ) );
$month = (int) ( $_GET['m'] ?? $today->format( 'n' ) );
$year  = max( 2020, min( 2035, $year ) );
$month = max( 1, min( 12, $month ) );

$cal_data    = $db->get_calendar_month( $year, $month, $type );
$first_day   = new \DateTime( sprintf( '%04d-%02d-01', $year, $month ) );
$last_day    = (int) $first_day->format( 't' );
$start_wday  = (int) $first_day->format( 'N' );

$prev_dt = clone $first_day;
$prev_dt->modify( '-1 month' );
$next_dt = clone $first_day;
$next_dt->modify( '+1 month' );

$today_str  = $today->format( 'Y-m-d' );
$month_ru   = array( '', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
	'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь' );
$weekdays   = array( 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' );
$suffix     = $cur_dept ? $cur_dept['file_suffix'] : '';

$upload_dir = wp_upload_dir();
$files_dir  = $upload_dir['basedir'] . '/meal-menu';
$files_url  = $upload_dir['baseurl'] . '/meal-menu';
$f_pattern  = $files_dir . '/' . sprintf( '%04d-%02d-*%s.xlsx', $year, $month, $suffix );

$other_suffixes = array();
foreach ( $enabled_depts as $dep ) {
	if ( $dep['file_suffix'] !== '' && $dep['file_suffix'] !== $suffix ) {
		$other_suffixes[] = $dep['file_suffix'];
	}
}

$existing_files = array();
foreach ( glob( $f_pattern ) ?: array() as $f ) {
	$name_no_ext = basename( $f, '.xlsx' );
	if ( $suffix === '' ) {
		$skip = false;
		foreach ( $other_suffixes as $os ) {
			if ( str_ends_with( $name_no_ext, $os ) ) {
				$skip = true;
				break;
			}
		}
		if ( $skip ) {
			continue;
		}
	}
	$existing_files[ substr( basename( $f ), 0, 10 ) ] = true;
}

$cycle_len     = $db->get_cycle_length( $type );
$tpls_ordered  = $db->get_templates_ordered( $type );
$templates_map = array();
foreach ( $tpls_ordered as $dn => $tid ) {
	$templates_map[ $tid ] = $dn;
}

$tpls_all         = $db->get_templates( $type );
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

global $wpdb;
$c = $db->get_table_name( 'calendar' );
$t = $db->get_table_name( 'templates' );
$last_before_row = $wpdb->get_row( $wpdb->prepare(
	"SELECT t.day_number FROM $c c
	 LEFT JOIN $t t ON t.id = c.template_id
	 WHERE c.date < %s AND c.school_type = %s AND c.template_id IS NOT NULL
	 ORDER BY c.date DESC LIMIT 1",
	$first_day->format( 'Y-m-d' ), $type
), ARRAY_A );

$default_start_day = $day_num_keys[0] ?? 1;
if ( $last_before_row && $last_before_row['day_number'] ) {
	$last_day_num = (int) $last_before_row['day_number'];
	$pos = array_search( $last_day_num, $day_num_keys, true );
	if ( $pos !== false ) {
		$default_start_day = $day_num_keys[ ( $pos + 1 ) % count( $day_num_keys ) ];
	}
}

$cur_workdays = $cur_dept ? explode( ',', $cur_dept['workdays'] ) : array( '1', '2', '3', '4', '5' );

$month_from    = sprintf( '%04d-%02d-01', $year, $month );
$month_to      = gmdate( 'Y-m-t', strtotime( $month_from ) );
$vacation_days = $db->get_vacation_days_for_range( $month_from, $month_to );
$cur_period    = $db->get_current_period( $type, $today_str );
?>
<div class="wrap meal-menu-wrap" style="max-width:960px">
	<div class="flex items-center justify-between mb-2">
		<h1 class="page-title" style="border:none;margin:0"><?php _e( 'Календарь меню', 'meal-menu' ); ?></h1>
		<div class="flex gap-2">
			<button type="button" id="btn-recalc" class="btn btn-outline btn-sm"><?php _e( 'Пересчитать', 'meal-menu' ); ?></button>
			<?php if ( ! empty( $cur_dept['publish_xlsx'] ) ): ?>
			<button type="button" id="btn-gen-files" class="btn btn-primary btn-sm"><?php _e( 'Создать файлы', 'meal-menu' ); ?></button>
			<button type="button" id="btn-files" class="btn btn-outline btn-sm"><?php _e( 'Файлы', 'meal-menu' ); ?></button>
			<?php endif; ?>
			<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $type ); ?>" class="btn btn-outline btn-sm"><?php _e( 'Шаблоны', 'meal-menu' ); ?></a>
		</div>
	</div>

	<div class="tab-bar">
		<?php foreach ( $type_labels as $t => $label ): ?>
		<a href="admin.php?page=meal-calendar&type=<?php echo esc_attr( $t ); ?>&y=<?php echo $year; ?>&m=<?php echo $month; ?>"
		   class="tab-item<?php echo $type === $t ? ' active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<div class="panel">
		<div class="cycle-info" style="font-size:.78rem;color:var(--muted);padding:6px 0 0">
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

			<?php for ( $i = 1; $i < $start_wday; $i++ ): ?>
				<div class="cal-cell empty"></div>
			<?php endfor; ?>

			<?php for ( $d = 1; $d <= $last_day; $d++ ):
				$date_str = sprintf( '%04d-%02d-%02d', $year, $month, $d );
				$wday     = (int) ( new \DateTime( $date_str ) )->format( 'N' );
				$entry    = $cal_data[ $date_str ] ?? null;
				$is_today = ( $date_str === $today_str );

				$is_vacation = isset( $vacation_days[ $date_str ] );
				$vac_label   = $is_vacation ? $vacation_days[ $date_str ] : '';

				$cls = 'cal-cell';
				if ( $wday >= 6 ) {
					$cls .= ' weekend';
				}
				if ( $is_vacation ) {
					$cls .= ' vacation';
				} elseif ( $entry && $entry['template_id'] === null ) {
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

				$entry_json = $entry ? json_encode( array(
					'template_id'    => $entry['template_id'],
					'day_number'     => $entry['template_id'] ? ( $templates_map[ (int) $entry['template_id'] ] ?? null ) : null,
					'label'          => $entry['template_label'] ?? null,
					'school'         => $entry['school'] ?? '',
					'dept'           => $entry['dept']   ?? '',
					'is_cycle_start' => (int) ( $entry['is_cycle_start'] ?? 0 ),
				), JSON_UNESCAPED_UNICODE ) : 'null';
			?>
			<div class="<?php echo $cls; ?>"
				data-date="<?php echo $date_str; ?>"
				data-entry="<?php echo esc_attr( $entry_json ); ?>"
				<?php echo $is_vacation ? 'data-vacation="' . esc_attr( $vac_label ) . '"' : ''; ?>
				<?php echo isset( $existing_files[ $date_str ] ) ? 'data-has-file="1"' : ''; ?>>
				<div class="cal-day"><?php echo $d; ?></div>
				<?php if ( $is_vacation ): ?>
					<div class="cal-vacation" style="font-size:.72rem;color:#7a5c9a;line-height:1.2"><?php _e( 'каникулы', 'meal-menu' ); ?></div>
				<?php elseif ( $entry && $entry['template_id'] ): ?>
					<div class="cal-label"><?php echo esc_html( $entry['template_label'] ); ?></div>
				<?php elseif ( $entry && $entry['template_id'] === null ): ?>
					<div class="cal-no-school"><?php _e( 'выходной', 'meal-menu' ); ?></div>
				<?php endif; ?>
			</div>
			<?php endfor; ?>

			<?php
			$end_wday = (int) ( new \DateTime( sprintf( '%04d-%02d-%02d', $year, $month, $last_day ) ) )->format( 'N' );
			for ( $i = $end_wday + 1; $i <= 7; $i++ ): ?>
				<div class="cal-cell empty"></div>
			<?php endfor; ?>
		</div>

		<div class="legend" style="margin-top:12px">
			<div class="legend-item"><div class="legend-dot has-menu"></div> <?php _e( 'Меню', 'meal-menu' ); ?></div>
			<div class="legend-item"><div class="legend-dot holiday"></div> <?php _e( 'Выходной', 'meal-menu' ); ?></div>
			<div class="legend-item"><div class="legend-dot" style="background:#c4a8e0"></div> <?php _e( 'Каникулы', 'meal-menu' ); ?></div>
			<div class="legend-item"><div class="legend-dot no-menu"></div> <?php _e( 'Не задан', 'meal-menu' ); ?></div>
		</div>
	</div>
</div>

<div id="day-popup" style="display:none;position:fixed;z-index:500;background:#fff;border:1px solid var(--border-light);border-top:3px solid var(--orange);padding:14px 18px;min-width:200px;max-width:280px;box-shadow:0 4px 16px rgba(0,0,0,.15)">
	<div class="day-popup-title" id="popup-title" style="font-size:.88rem;font-weight:bold;color:var(--text);margin-bottom:4px"></div>
	<div class="day-popup-status" id="popup-status" style="font-size:.82rem;color:var(--muted);margin-bottom:12px"></div>
	<div class="day-popup-actions" id="popup-actions" style="display:flex;flex-direction:column;gap:6px"></div>
</div>

<div id="files-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
	<div style="background:#fff;border-top:3px solid var(--orange);width:95%;max-width:700px;max-height:80vh;display:flex;flex-direction:column">
		<div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--border-light)">
			<span style="font-weight:bold;font-size:.95rem"><?php printf( __( 'Файлы — %s %d', 'meal-menu' ), $month_ru[ $month ], $year ); ?></span>
			<button id="fm-close-btn" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--muted)">&times;</button>
		</div>
		<div id="fm-tabs" style="display:flex;gap:0;border-bottom:1px solid var(--border-light);padding:0 20px;"></div>
		<div id="fm-body" style="padding:16px 20px;overflow-y:auto;flex:1;"></div>
	</div>
</div>

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
	var publishXlsx  = <?php echo ! empty( $cur_dept['publish_xlsx'] ) ? 'true' : 'false'; ?>;
	var vacationDays = <?php echo json_encode( array_keys( $vacation_days ) ); ?>;
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
		cell.classList.remove('has-menu', 'holiday', 'cycle-start');
		if (entry && entry.template_id) {
			cell.classList.add('has-menu');
			if (entry.is_cycle_start) cell.classList.add('cycle-start');
		} else if (entry && entry.template_id === null) {
			cell.classList.add('holiday');
		}
		var dayEl = cell.querySelector('.cal-day');
		var labelEl = cell.querySelector('.cal-label');
		var noSchoolEl = cell.querySelector('.cal-no-school');
		if (entry && entry.template_id) {
			if (!labelEl) {
				labelEl = document.createElement('div');
				labelEl.className = 'cal-label';
				cell.appendChild(labelEl);
			}
			labelEl.textContent = entry.label;
			if (noSchoolEl) noSchoolEl.remove();
		} else if (entry && entry.template_id === null) {
			if (!noSchoolEl) {
				noSchoolEl = document.createElement('div');
				noSchoolEl.className = 'cal-no-school';
				cell.appendChild(noSchoolEl);
			}
			noSchoolEl.textContent = 'выходной';
			if (labelEl) labelEl.remove();
		} else {
			if (labelEl) labelEl.remove();
			if (noSchoolEl) noSchoolEl.remove();
		}
	}

	// ── Попап дня ───────────────────────────────────────────
	var popup = document.getElementById('day-popup');
	var popupTitle = document.getElementById('popup-title');
	var popupStatus = document.getElementById('popup-status');
	var popupActions = document.getElementById('popup-actions');

	document.getElementById('cal-grid').addEventListener('click', function(e) {
		var cell = e.target.closest('.cal-cell');
		if (!cell || cell.classList.contains('empty') || cell.dataset.vacation) return;
		var date = cell.dataset.date;
		var entry = getCellEntry(cell);
		var hasEntry = entry && entry.template_id;
		var wd = getCellWday(cell);
		var isWorkday = defaultWorkdays.indexOf(wd) >= 0;
		var isVacDay = vacationDays.indexOf(date) >= 0;

		popupTitle.textContent = date;
		var statusHtml = '';
		if (isVacDay) statusHtml = '<span style="color:#7a5c9a">Каникулы</span>';
		else if (!isWorkday) statusHtml = '<span style="color:var(--muted)">Выходной день</span>';
		else if (hasEntry) statusHtml = '<strong>' + escHtml(entry.label) + '</strong>';
		else statusHtml = '<span style="color:var(--error)">Не заполнено</span>';
		popupStatus.innerHTML = statusHtml;

		var html = '';
		if (hasEntry) {
			html += '<button class="btn btn-danger btn-sm" data-action="delete">Очистить</button>';
		}
		if (!hasEntry && isWorkday && !isVacDay) {
			html += '<div style="font-size:.8rem;color:var(--muted);margin-bottom:4px">День меню №:</div>';
			html += '<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px">';
			dayNumKeys.forEach(function(dn) {
				html += '<button class="btn btn-outline btn-sm" data-action="set" data-day="' + dn + '" style="padding:2px 8px;font-size:.78rem">' + dn + '</button>';
			});
			html += '</div>';
			if (cycleLen > 0) {
				html += '<button class="btn btn-outline btn-sm" data-action="apply-cycle" data-day="' + defaultStartDay + '">Применить цикл с этого дня</button>';
			}
			html += '<button class="btn btn-outline btn-sm" data-action="set-holiday" style="margin-top:4px">Выходной (без меню)</button>';
		}
		if (!hasEntry && isWorkday && !isVacDay && cycleLen > 0) {
			html += '<button class="btn btn-outline btn-sm" data-action="apply-from-here" style="margin-top:4px">Применить с сегодняшнего дня</button>';
		}
		if (hasEntry) {
			html += '<button class="btn btn-outline btn-sm" data-action="apply-from-this">Применить цикл с этого дня</button>';
		}
		popupActions.innerHTML = html;

		var r = cell.getBoundingClientRect();
		var left = r.left + window.scrollX;
		var top = r.bottom + window.scrollY + 4;
		if (top + 300 > window.innerHeight) top = r.top + window.scrollY - 300;
		popup.style.left = Math.min(left, window.innerWidth - 300) + 'px';
		popup.style.top = top + 'px';
		popup.style.display = 'block';
	});

	popupActions.addEventListener('click', function(e) {
		var btn = e.target.closest('button[data-action]');
		if (!btn) return;
		var cell = document.querySelector('.cal-cell[data-date="' + popupTitle.textContent + '"]');
		if (!cell) return;
		var action = btn.dataset.action;

		if (action === 'set') {
			var dayNum = parseInt(btn.dataset.day);
			apiPost({ action: 'save', date: popupTitle.textContent, type: curType, is_school: true, day_num: dayNum }, function(r) {
				if (r.ok) {
					var entry = { template_id: r.template_id, day_number: r.day_num, label: r.label, is_cycle_start: 0 };
					updateCell(cell, entry);
				}
			});
			popup.style.display = 'none';
		} else if (action === 'set-holiday') {
			apiPost({ action: 'save', date: popupTitle.textContent, type: curType, is_school: false }, function(r) {
				if (r.ok) {
					var entry = { template_id: null, day_number: null, label: null, is_cycle_start: 0 };
					updateCell(cell, entry);
				}
			});
			popup.style.display = 'none';
		} else if (action === 'delete') {
			apiPost({ action: 'delete', date: popupTitle.textContent, type: curType }, function(r) {
				if (r.ok) updateCell(cell, null);
			});
			popup.style.display = 'none';
		} else if (action === 'apply-cycle') {
			var day = parseInt(btn.dataset.day);
			if (!confirm('Заполнить все рабочие дни до конца месяца циклом, начиная с дня №' + day + '?')) return;
			apiPost({ action: 'apply_cycle', date: popupTitle.textContent, type: curType, start_day: day }, function(r) {
				if (r.ok) { location.reload(); }
			});
		} else if (action === 'apply-from-here' || action === 'apply-from-this') {
			if (!confirm('Пересчитать весь период по циклу? Даты с уже назначенным меню будут пропущены.')) return;
			apiPost({ action: 'recalc_period', type: curType }, function(r) {
				if (r.ok) { location.reload(); }
			});
		}
	});

	document.addEventListener('click', function(e) {
		if (!popup.contains(e.target) && !e.target.closest('.cal-cell')) {
			popup.style.display = 'none';
		}
	});

	// ── Пересчитать ──────────────────────────────────────────
	document.getElementById('btn-recalc').addEventListener('click', function() {
		if (!confirm('Пересчитать весь ' + curType + '-период? Даты с меню не изменятся.')) return;
		apiPost({ action: 'recalc_period', type: curType }, function(r) {
			if (r.ok) { location.reload(); }
		});
	});

	// ── Создать файлы ────────────────────────────────────────
	if (document.getElementById('btn-gen-files')) {
		document.getElementById('btn-gen-files').addEventListener('click', function() {
			if (!confirm('Создать Excel-файлы меню для всех дней месяца?')) return;
			apiPost({ action: 'generate_files', type: curType, year: curYear, month: curMonth }, function(r) {
				if (r.ok) alert('Создано файлов: ' + r.count);
			});
		});
	}

	// ── Модалка файлов ───────────────────────────────────────
	if (document.getElementById('btn-files')) {
		var fmModal = document.getElementById('files-modal');
		var fmBody = document.getElementById('fm-body');
		var fmTabs = document.getElementById('fm-tabs');

		document.getElementById('btn-files').addEventListener('click', function() {
			fmModal.style.display = 'flex';
			loadFiles();
		});
		document.getElementById('fm-close-btn').addEventListener('click', function() {
			fmModal.style.display = 'none';
		});
		fmModal.addEventListener('click', function(e) {
			if (e.target === fmModal) fmModal.style.display = 'none';
		});

		function loadFiles() {
			fmTabs.innerHTML = '';
			fmBody.innerHTML = '<div style="color:var(--muted);text-align:center;padding:24px">Загрузка...</div>';
			jQuery.post(ajaxUrl + '?action=meal_list_files', { nonce: nonce }, function(r) {
				if (!r.ok) { fmBody.innerHTML = '<div class="fm-empty">Ошибка загрузки</div>'; return; }
				var depts = r.departments;
				var codes = Object.keys(depts);
				if (codes.length === 0) { fmBody.innerHTML = '<div class="fm-empty">Нет файлов</div>'; return; }
				var activeCode = codes.indexOf(curType) >= 0 ? curType : codes[0];
				function renderTab(code) {
					return '<button class="fm-tab' + (code === activeCode ? ' active' : '') + '" data-code="' + code + '" style="padding:8px 16px;cursor:pointer;border-bottom:2px solid ' + (code === activeCode ? 'var(--orange)' : 'transparent') + ';font-size:.85rem;color:' + (code === activeCode ? 'var(--orange)' : 'var(--muted)') + ';background:none;border-top:none;border-left:none;border-right:none;font-weight:' + (code === activeCode ? 'bold' : 'normal') + '">' + escHtml(depts[code].label) + '</button>';
				}
				fmTabs.innerHTML = codes.map(renderTab).join('');
				renderFiles(activeCode, depts);
			}, 'json');
		}

		fmTabs.addEventListener('click', function(e) {
			var tab = e.target.closest('.fm-tab');
			if (!tab) return;
			fmTabs.querySelectorAll('.fm-tab').forEach(function(t) {
				t.style.borderBottomColor = 'transparent';
				t.style.color = 'var(--muted)';
				t.style.fontWeight = 'normal';
			});
			tab.style.borderBottomColor = 'var(--orange)';
			tab.style.color = 'var(--orange)';
			tab.style.fontWeight = 'bold';
			jQuery.post(ajaxUrl + '?action=meal_list_files', { nonce: nonce }, function(r) {
				renderFiles(tab.dataset.code, r.departments);
			}, 'json');
		});

		function renderFiles(code, depts) {
			var files = depts[code] ? depts[code].files : [];
			if (files.length === 0) {
				fmBody.innerHTML = '<div style="color:var(--muted);text-align:center;padding:24px;font-size:.9rem">Нет файлов за этот месяц</div>';
				return;
			}
			var html = '<table class="fm-table" style="width:100%;border-collapse:collapse;font-size:.85rem"><thead><tr>';
			html += '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light);color:var(--muted);font-weight:normal;text-transform:uppercase;font-size:.75rem;letter-spacing:.05em">Файл</th>';
			html += '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light);color:var(--muted);font-weight:normal;text-transform:uppercase;font-size:.75rem;letter-spacing:.05em">Размер</th>';
			html += '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light);color:var(--muted);font-weight:normal;text-transform:uppercase;font-size:.75rem;letter-spacing:.05em">Изменён</th>';
			html += '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light);color:var(--muted);font-weight:normal;text-transform:uppercase;font-size:.75rem;letter-spacing:.05em"></th></tr></thead><tbody>';
			files.forEach(function(f) {
				html += '<tr><td style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light)"><a href="' + escHtml(f.url) + '" download>' + escHtml(f.name) + '</a></td>';
				html += '<td style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light)">' + escHtml(f.size) + '</td>';
				html += '<td style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light)">' + escHtml(f.modified) + '</td>';
				html += '<td style="padding:6px 8px;text-align:left;border-bottom:1px solid var(--border-light)"></td></tr>';
			});
			html += '</tbody></table>';
			fmBody.innerHTML = html;
		}
	}
})();
</script>
