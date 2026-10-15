<?php
/** Run from the repository root: php tests/run.php */

require __DIR__ . '/bootstrap.php';

function expect_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
	}
}
function expect_contains( string $needle, string $html, string $message ): void {
	if ( ! str_contains( $html, $needle ) ) { throw new RuntimeException( $message . ': missing ' . $needle ); }
}
function expect_absent( string $needle, string $html, string $message ): void {
	if ( str_contains( $html, $needle ) ) { throw new RuntimeException( $message . ': found ' . $needle ); }
}
function render_day( string $date, string $type ): string {
	$atts = array( 'date' => $date, 'type' => $type );
	ob_start();
	try { require MEAL_MENU_DIR . 'templates/public/day.php'; return ob_get_contents(); }
	finally { ob_end_clean(); }
}
function render_modal( Test_DB $db, string $date, string $type ): array {
	$_GET['camp'] = '';
	try { require MEAL_MENU_DIR . 'includes/ajax/ajax-get-day-menu.php'; }
	catch ( Test_Json_Response $response ) { return $response->data; }
	throw new RuntimeException( 'Modal did not send a JSON response' );
}
function save_request( Test_DB $db, array $data ): array {
	try { require MEAL_MENU_DIR . 'includes/ajax/ajax-save-day.php'; }
	catch ( Test_Json_Response $response ) { return $response->data; }
	throw new RuntimeException( 'Save handler did not send a JSON response' );
}
function read_xlsx_cell( string $path, string $cell ): string {
	// Phar reads ZIP files without ext-zip. The temporary alias uses its supported extension.
	$zip_path = $path . '.zip';
	copy( $path, $zip_path );
	try {
		$zip = new PharData( $zip_path );
		$sheet = simplexml_load_string( $zip['xl/worksheets/sheet1.xml']->getContent() );
		$sheet->registerXPathNamespace( 's', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main' );
		$node = $sheet->xpath( '//s:c[@r="' . $cell . '"]' )[0] ?? null;
		if ( ! $node ) { throw new RuntimeException( 'Missing spreadsheet cell ' . $cell ); }
		$value = (string) $node->v;
		if ( (string) $node['t'] === 's' ) {
			$strings = simplexml_load_string( $zip['xl/sharedStrings.xml']->getContent() );
			$value = (string) $strings->si[ (int) $value ]->t;
		}
		return $value;
	} finally { unset( $zip ); unlink( $zip_path ); }
}
function read_xlsx_cell_or_empty( string $path, string $cell ): string {
	try { return read_xlsx_cell( $path, $cell ); }
	catch ( RuntimeException $error ) { return ''; } // Незаписанная ячейка и пустая ячейка значат одно и то же.
}

$tests = array(
	'merged calendar chooses primary template and preserves schedule flags' => static function( Test_DB $db ): void {
		expect_same( 'main', $db->get_calendar_source_type( 'sm' ), 'Schedule should come from main' );
		$day = $db->get_menu_calendar_day( '2026-10-05', 'sm' );
		expect_same( 201, (int) $day['template_id'], 'Primary template must replace source template' );
		expect_same( 1, (int) $day['day_number'], 'Cycle day comes from source calendar' );
		expect_same( '2026-10-05', $day['date'], 'Source date retained' );
		expect_same( 1, (int) $day['is_cycle_start'], 'Source cycle-start flag retained' );
		expect_same( 0, (int) $day['iterate_number'], 'Source iteration flag retained' );
		expect_same( 'Primary day 1', $day['template_label'], 'Local label should be displayed' );
		expect_same( 0, (int) $day['is_boarding'], 'Local boarding flag should control meals' );
		expect_same( false, ! empty( $day['menu_missing'] ), 'Existing local template is available' );
		$month = $db->get_menu_calendar_month( 2026, 10, 'sm' );
		expect_same( $day, $month['2026-10-05'], 'Single-day and month resolution must agree' );
		expect_same( array_keys( $db->calendar['main'] ), array_keys( $month ), 'Shared calendar supplies all dates' );
	},
	'source department retains its own menu' => static function( Test_DB $db ): void {
		expect_same( 'main', $db->get_calendar_source_type( 'main' ), 'Unmerged source stays main' );
		$day = $db->get_menu_calendar_day( '2026-10-05', 'main' );
		expect_same( 101, (int) $day['template_id'], 'Senior template retained' );
		expect_same( 'Senior day 1', $day['template_label'], 'Senior label retained' );
		expect_same( 1, (int) $day['is_boarding'], 'Senior boarding retained' );
		expect_same( null, $db->get_menu_calendar_day( '2026-10-09', 'sm' ), 'No schedule produces no menu' );
	},
	'missing local cycle template never falls back to senior meals' => static function( Test_DB $db ): void {
		$day = $db->get_menu_calendar_day( '2026-10-06', 'sm' );
		expect_same( null, $day['template_id'], 'Missing primary day 2 must not use senior day 2' );
		expect_same( 2, (int) $day['day_number'], 'Missing local template retains scheduled cycle day' );
		expect_same( true, ! empty( $day['menu_missing'] ), 'A missing menu differs from a holiday' );
		expect_same( null, $db->get_menu_calendar_month( 2026, 10, 'sm' )['2026-10-06']['template_id'], 'Month also rejects fallback' );
	},
	'holidays and iteration-only days remain without a menu' => static function( Test_DB $db ): void {
		foreach ( array( '2026-10-07' => 0, '2026-10-08' => 1 ) as $date => $iterate ) {
			$day = $db->get_menu_calendar_day( $date, 'sm' );
			expect_same( null, $day['template_id'], 'Non-menu day retained' );
			expect_same( false, ! empty( $day['menu_missing'] ), 'Holiday is not a missing primary template' );
			expect_same( $iterate, (int) $day['iterate_number'], 'Iteration flag retained' );
		}
	},
	'invalid merge target and cycles fall back to requested calendar' => static function( Test_DB $db ): void {
		$db->departments['sm']['merged_with'] = 'deleted';
		expect_same( 'sm', $db->get_calendar_source_type( 'sm' ), 'Deleted department cannot supply schedule' );
		$db->departments['sm']['merged_with'] = 'main';
		$db->departments['main']['is_enabled'] = 0;
		expect_same( 'sm', $db->get_calendar_source_type( 'sm' ), 'Disabled department cannot supply schedule' );
		$db->departments['main']['is_enabled'] = 1;
		$db->departments['sm']['merged_with'] = 'sm';
		expect_same( 'sm', $db->get_calendar_source_type( 'sm' ), 'Self merge cannot loop' );
		$db->departments['sm']['merged_with'] = 'main';
		$db->departments['main']['merged_with'] = 'sm';
		expect_same( 'sm', $db->get_calendar_source_type( 'sm' ), 'Cycle resolves safely for primary' );
		expect_same( 'main', $db->get_calendar_source_type( 'main' ), 'Cycle resolves safely for senior' );
		expect_same( 'unknown', $db->get_calendar_source_type( 'unknown' ), 'Unknown department is safe' );
	},
	'chains and siblings resolve to one calendar group' => static function( Test_DB $db ): void {
		$db->departments['ss'] = array( 'code' => 'ss', 'is_enabled' => 1, 'merged_with' => 'sm' );
		expect_same( 'main', $db->get_calendar_source_type( 'ss' ), 'Chained department uses terminal source' );
		foreach ( array( 'sm', 'main', 'ss' ) as $type ) {
			$group = $db->get_calendar_group_types( $type );
			sort( $group );
			expect_same( array( 'main', 'sm', 'ss' ), $group, 'Changes from any member must reach the whole calendar group' );
		}
	},
	'bulk save from primary edits the canonical source schedule' => static function( Test_DB $db ): void {
		$local_before = $db->calendar['sm'];
		$response = save_request( $db, array( 'action' => 'bulk_save', 'type' => 'sm', 'days' => array(
			array( 'date' => '2026-10-09', 'day_num' => 1, 'is_cycle_start' => 1 ),
			array( 'date' => '2026-10-05', 'day_num' => -1 ),
		) ) );
		expect_same( true, $response['ok'], 'Bulk save succeeds' );
		expect_same( 101, $db->calendar['main']['2026-10-09']['template_id'], 'Stored calendar references source template' );
		expect_same( 201, $db->get_menu_calendar_day( '2026-10-09', 'sm' )['template_id'], 'Published primary menu resolves to local template' );
		expect_same( null, $db->get_calendar_day( '2026-10-05', 'main' ), 'Delete affects canonical calendar' );
		expect_same( $local_before, $db->calendar['sm'], 'Stale local calendar is not mutated' );
	},
	'calendar links keep the requested department and missing menus are not holidays' => static function( Test_DB $db ): void {
		$html = \Meal_Menu\Shortcodes::render_calendar_body( 'sm', 2026, 10 );
		expect_contains( 'data-date="2026-10-05" data-type="sm"', $html, 'Primary day must request primary modal' );
		expect_contains( 'Primary day 1', $html, 'Calendar uses local label' );
		expect_absent( 'Senior day 1', $html, 'Calendar does not advertise senior template' );
		expect_absent( 'data-date="2026-10-06"', $html, 'Missing primary menu has no menu link' );
		expect_same( 1, preg_match( '/<div class="([^"]*)">\s*<div class="meal-cal-day">6<\/div>(.*?)<\/div>/s', $html, $missing ), 'Missing-menu calendar cell exists' );
		expect_same( false, str_contains( $missing[1] ?? '', 'holiday' ), 'Missing local menu must not be styled as holiday' );
	},
	'legacy calendar cache cannot restore senior links in primary calendar' => static function( Test_DB $db ): void {
		$GLOBALS['test_transients']['meal_cal_sm_2026_10_0'] = '<a data-type="main">Poisoned senior calendar</a>';
		$html = \Meal_Menu\Shortcodes::render_calendar_body( 'sm', 2026, 10 );
		expect_absent( 'Poisoned senior calendar', $html, 'Calendar ignores HTML cached by the old projection' );
		expect_contains( 'data-date="2026-10-05" data-type="sm"', $html, 'Fresh calendar keeps primary department' );
	},
	'calendar invalidation bypasses stale transients in persistent object caches' => static function( Test_DB $db ): void {
		$old_html = \Meal_Menu\Shortcodes::render_calendar_body( 'sm', 2026, 10 );
		expect_absent( 'data-date="2026-10-06"', $old_html, 'Day 2 initially has no primary template' );
		$db->templates[202] = array( 'id' => 202, 'school_type' => 'sm', 'day_number' => 2, 'label' => 'New primary day 2', 'is_boarding' => 0 );
		\Meal_Menu\Shortcodes::invalidate_calendar_cache();
		expect_same( $old_html, get_transient( 'meal_cal_v2_0_sm_2026_10_0' ), 'Persistent old entry survives SQL deletion' );
		expect_same( 1, get_option( 'meal_calendar_cache_version' ), 'Invalidation advances cache version' );
		$html = \Meal_Menu\Shortcodes::render_calendar_body( 'sm', 2026, 10 );
		expect_contains( 'data-date="2026-10-06" data-type="sm"', $html, 'New version resolves the added primary template' );
		expect_contains( 'New primary day 2', $html, 'Calendar reflects edited templates despite persistent old cache' );
	},
	'public day and AJAX modal show primary portions and local boarding policy' => static function( Test_DB $db ): void {
		$day = render_day( '2026-10-05', 'sm' );
		$modal = render_modal( $db, '2026-10-05', 'sm' );
		expect_same( true, $modal['ok'], 'Modal succeeds with shared schedule and local template' );
		foreach ( array( $day, $modal['html'] ) as $html ) {
			expect_contains( 'Primary bread', $html, 'Local dish displayed' );
			expect_contains( '>20,0<', $html, 'Primary portion displayed' );
			expect_absent( 'Senior bread', $html, 'Senior dish never leaks into primary menu' );
			expect_absent( 'Hidden primary dinner', $html, 'Local boarding policy excludes dinner' );
		}
		$missing = render_modal( $db, '2026-10-06', 'sm' );
		expect_same( false, $missing['ok'], 'Missing local template is not a successful menu' );
		expect_absent( 'Senior', $missing['html'], 'Missing menu does not reveal source dishes' );
	},
	'AJAX ignores old day caches and immediately reflects edited portions' => static function( Test_DB $db ): void {
		foreach ( array( 'meal_day_', 'meal_day_v2_' ) as $prefix ) {
			$GLOBALS['test_transients'][ $prefix . '2026-10-05_sm_0' ] = '<p>Poisoned senior menu</p>';
		}
		$response = render_modal( $db, '2026-10-05', 'sm' );
		expect_same( true, $response['ok'], 'Fresh local modal succeeds' );
		expect_absent( 'Poisoned senior menu', $response['html'], 'Existing day caches are ignored' );
		expect_same( 1, preg_match( '/Primary bread<\/td>\s*<td class="num">20,0<\/td>/', $response['html'] ), 'Modal resolves local portion' );
		$db->items[201][0]['grams'] = 21;
		$updated = render_modal( $db, '2026-10-05', 'sm' );
		expect_same( 1, preg_match( '/Primary bread<\/td>\s*<td class="num">21,0<\/td>/', $updated['html'] ), 'A later request reflects the edit immediately' );
	},
	'stale XLSX for a missing local template is hidden and left for explicit regeneration' => static function( Test_DB $db ): void {
		$meal_dir = $GLOBALS['test_upload_dir'] . '/meal-menu';
		wp_mkdir_p( $meal_dir );
		$path = $meal_dir . '/2026-10-06-sm.xlsx';
		$old_content = 'Previously exported incorrect senior menu';
		file_put_contents( $path, $old_content );
		$published_before = $GLOBALS['test_published_files'];
		$html = render_day( '2026-10-06', 'sm' );
		expect_contains( 'Меню на этот день не опубликовано.', $html, 'Missing local menu is reported' );
		expect_absent( 'meal-download-btn', $html, 'A leftover export must not be offered as current menu' );
		expect_absent( '2026-10-06-sm.xlsx', $html, 'Stale filename is not linked' );
		expect_same( null, \Meal_Menu\Excel_Daily::generate( '2026-10-06', 'sm' ), 'Generator refuses to replace missing local menu with senior menu' );
		expect_same( $old_content, file_get_contents( $path ), 'Old file is left unchanged for explicit removal or regeneration' );
		expect_same( $published_before, $GLOBALS['test_published_files'], 'Stale export is not republished' );
	},
	'standalone download uses configured department file suffix' => static function( Test_DB $db ): void {
		$db->departments['sm']['file_suffix'] = '-primary-custom';
		$meal_dir = $GLOBALS['test_upload_dir'] . '/meal-menu';
		wp_mkdir_p( $meal_dir );
		file_put_contents( $meal_dir . '/2026-10-05-primary-custom.xlsx', 'Existing primary export' );
		$html = render_day( '2026-10-05', 'sm' );
		expect_contains( 'meal-download-btn', $html, 'Available local menu can offer an existing export' );
		expect_contains( 'href="https://example.test/uploads/meal-menu/2026-10-05-primary-custom.xlsx"', $html, 'Download respects configured suffix' );
	},
	'daily XLSX exports local portions under the correct filename' => static function( Test_DB $db ): void {
		$primary = \Meal_Menu\Excel_Daily::generate( '2026-10-05', 'sm' );
		$senior = \Meal_Menu\Excel_Daily::generate( '2026-10-05', 'main' );
		expect_same( '2026-10-05-sm.xlsx', basename( $primary ), 'Primary suffix retained' );
		expect_same( '2026-10-05.xlsx', basename( $senior ), 'Senior filename unchanged' );
		expect_same( 'Primary bread', read_xlsx_cell( $primary, 'D4' ), 'Primary Excel dish' );
		expect_same( 20.0, (float) read_xlsx_cell( $primary, 'E4' ), 'Primary Excel portion' );
		expect_same( 'Primary department', read_xlsx_cell( $primary, 'F1' ), 'Primary Excel department' );
		expect_same( 'Senior bread', read_xlsx_cell( $senior, 'D4' ), 'Senior Excel dish retained' );
		expect_same( 40.0, (float) read_xlsx_cell( $senior, 'E4' ), 'Senior Excel portion retained' );
		expect_same( null, \Meal_Menu\Excel_Daily::generate( '2026-10-06', 'sm' ), 'Missing local template does not generate senior Excel' );
		expect_same( null, \Meal_Menu\Excel_Daily::generate( '2026-10-07', 'sm' ), 'Holiday does not generate Excel' );
		expect_same( array( $primary, $senior ), $GLOBALS['test_published_files'], 'Only valid department exports are published' );
	},
	'filling to the end of month keeps vacation days without a menu' => static function( Test_DB $db ): void {
		$db->vacations = array( array( 'label' => 'Осенние каникулы', 'date_from' => '2026-10-12', 'date_to' => '2026-10-16' ) );
		// Popup flow: first day is saved explicitly, the rest of the month comes from apply_cycle.
		save_request( $db, array( 'action' => 'save', 'type' => 'sm', 'date' => '2026-10-05', 'is_school' => true, 'day_num' => 1, 'is_cycle_start' => 1 ) );
		$response = save_request( $db, array( 'action' => 'apply_cycle', 'type' => 'sm', 'date' => '2026-10-06', 'start_day' => 2, 'end_date' => '2026-10-31', 'overwrite' => true ) );
		expect_same( true, $response['ok'], 'Applying the cycle succeeds' );
		foreach ( array( '2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16' ) as $date ) {
			$row = $db->get_calendar_day( $date, 'main' );
			expect_same( null, $row['template_id'] ?? null, 'Vacation day must not receive a menu' );
		}
		expect_same( 101, (int) $db->get_calendar_day( '2026-10-19', 'main' )['template_id'], 'Five vacation days must not consume cycle numbers (parity of the 2-day cycle)' );
		// Safety net: a menu left over on a vacation day must not reach the files.
		$db->save_calendar_day( '2026-10-13', 102, 'Test school', 'Source', 'main', 0, 0 );
		expect_same( null, \Meal_Menu\Excel_Daily::generate( '2026-10-13', 'main' ), 'A leftover menu on a vacation day produces no daily XLSX' );
		$db->departments['main']['ignore_vacations'] = 1;
		save_request( $db, array( 'action' => 'apply_cycle', 'type' => 'main', 'date' => '2026-10-12', 'start_day' => 1, 'end_date' => '2026-10-16', 'overwrite' => true ) );
		expect_same( 101, (int) $db->get_calendar_day( '2026-10-12', 'main' )['template_id'], 'Department ignoring vacations keeps its schedule' );
	},
	'KP keeps vacation days empty' => static function( Test_DB $db ): void {
		$db->vacations = array( array( 'label' => 'Осенние каникулы', 'date_from' => '2026-10-12', 'date_to' => '2026-10-16' ) );
		$db->save_calendar_day( '2026-10-01', 101, 'Test school', 'Source', 'main', 1, 0 );   // Рабочий четверг.
		$db->save_calendar_day( '2026-10-12', 101, 'Test school', 'Source', 'main', 0, 0 );   // Меню осталось на дне каникул.
		$path = \Meal_Menu\Excel_KP::generate( 2026, 'main' );
		expect_same( '1', read_xlsx_cell( $path, 'B11' ), 'Working day keeps its cycle number in KP' );
		expect_same( '', read_xlsx_cell_or_empty( $path, 'M11' ), 'Vacation day stays empty in KP' );
	},
);

$GLOBALS['test_upload_dir'] = sys_get_temp_dir() . '/meal-menu-tests-' . bin2hex( random_bytes( 6 ) );
$GLOBALS['test_published_files'] = array();
$failures = 0;
try {
	foreach ( $tests as $name => $test ) {
		$db = new Test_DB();
		$db->install();
		try { $test( $db ); echo "PASS: $name\n"; }
		catch ( Throwable $error ) { ++$failures; fwrite( STDERR, "FAIL: $name\n" . $error->getMessage() . "\n" ); }
	}
} finally {
	// Only files in this runner's newly-created directory are removed.
	foreach ( glob( $GLOBALS['test_upload_dir'] . '/meal-menu/*' ) ?: array() as $file ) { unlink( $file ); }
	if ( is_dir( $GLOBALS['test_upload_dir'] . '/meal-menu' ) ) { rmdir( $GLOBALS['test_upload_dir'] . '/meal-menu' ); }
	if ( is_dir( $GLOBALS['test_upload_dir'] ) ) { rmdir( $GLOBALS['test_upload_dir'] ); }
}
echo count( $tests ) . ' tests, ' . $failures . " failures\n";
exit( $failures ? 1 : 0 );
