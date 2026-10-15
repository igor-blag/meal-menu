<?php
/** Minimal WordPress boundary for standalone regression tests. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MEAL_MENU_DIR', dirname( __DIR__ ) . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'ARRAY_A', 'ARRAY_A' );

require_once MEAL_MENU_DIR . 'includes/class-db.php';
require_once MEAL_MENU_DIR . 'includes/class-shortcodes.php';
require_once MEAL_MENU_DIR . 'includes/class-excel-daily.php';
require_once MEAL_MENU_DIR . 'includes/class-excel-kp.php';

function __( string $text, string $domain = '' ): string { return $text; }
function _e( string $text, string $domain = '' ): void { echo $text; }
function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return esc_html( $value ); }
function esc_url( $value ): string { return esc_html( $value ); }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function current_time( string $format ): string { return gmdate( $format, strtotime( '2026-10-15 12:00:00 UTC' ) ); }
function get_option( string $name, $default = false ) { return $GLOBALS['test_options'][ $name ] ?? $default; }
function update_option( string $name, $value, $autoload = null ): bool { $GLOBALS['test_options'][ $name ] = $value; return true; }
function get_transient( string $name ) { return $GLOBALS['test_transients'][ $name ] ?? false; }
function set_transient( string $name, $value, int $ttl ): bool { $GLOBALS['test_transients'][ $name ] = $value; return true; }
function shortcode_atts( array $defaults, array $atts ): array { return array_merge( $defaults, $atts ); }
function add_query_arg( array $args, string $url = 'https://example.test/pitanie/' ): string { return $url . '?' . http_build_query( $args ); }
function remove_query_arg( string $name ): string { return 'https://example.test/pitanie/'; }
function wp_upload_dir(): array {
	return array( 'basedir' => $GLOBALS['test_upload_dir'], 'baseurl' => 'https://example.test/uploads' );
}
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0777, true ); }
function meal_publish_file( string $path ): void { $GLOBALS['test_published_files'][] = $path; }

class Test_Json_Response extends Error {
	public array $data;
	public function __construct( array $data ) { parent::__construct( 'JSON response' ); $this->data = $data; }
}
function wp_send_json( array $data ): void { throw new Test_Json_Response( $data ); }

/** Only item SQL may cross the fake database boundary; calendar logic stays real. */
class wpdb {
	private Test_DB $db;
	public string $options = 'test_options';
	public function __construct( Test_DB $db ) { $this->db = $db; }
	public function prepare( string $query, ...$args ): string {
		foreach ( $args as $arg ) {
			$query = preg_replace_callback( '/%[sd]/', static fn( array $match ): string => $match[0] === '%d' ? (string) (int) $arg : "'" . str_replace( "'", "''", (string) $arg ) . "'", $query, 1 );
		}
		return $query;
	}
	public function get_results( string $query, string $format ): array {
		if ( preg_match( '/FROM test_items WHERE template_id = (\d+)/', $query, $match ) ) {
			return $this->db->items[ (int) $match[1] ] ?? array();
		}
		throw new RuntimeException( 'Unexpected SQL in test: ' . $query );
	}
	public function get_row( string $query, string $format ): ?array {
		throw new RuntimeException( 'Unexpected calendar SQL in public view: ' . $query );
	}
	public function query( string $query ): int {
		if ( str_starts_with( $query, "DELETE FROM test_options WHERE option_name LIKE '_transient" ) ) {
			// Simulate Redis/Memcached: deleting option rows cannot evict cached transients.
			return 0;
		}
		throw new RuntimeException( 'Unexpected SQL in test: ' . $query );
	}
}

/** Raw storage is fake; inherited effective-calendar methods are production code. */
class Test_DB extends \Meal_Menu\DB {
	public array $departments;
	public array $templates;
	public array $calendar;
	public array $items;
	public array $vacations = array();

	public function __construct() {
		$this->departments = array(
			'sm' => array( 'code' => 'sm', 'label' => 'Primary', 'dept_name' => 'Primary department', 'is_enabled' => 1, 'merged_with' => 'main', 'file_suffix' => '-sm', 'workdays' => '1,2,3,4,5' ),
			'main' => array( 'code' => 'main', 'label' => 'Senior', 'dept_name' => 'Senior department', 'is_enabled' => 1, 'merged_with' => '', 'file_suffix' => '', 'workdays' => '1,2,3,4,5' ),
		);
		$this->templates = array(
			101 => array( 'id' => 101, 'school_type' => 'main', 'day_number' => 1, 'label' => 'Senior day 1', 'is_boarding' => 1 ),
			102 => array( 'id' => 102, 'school_type' => 'main', 'day_number' => 2, 'label' => 'Senior day 2', 'is_boarding' => 0 ),
			201 => array( 'id' => 201, 'school_type' => 'sm', 'day_number' => 1, 'label' => 'Primary day 1', 'is_boarding' => 0 ),
		);
		$this->calendar = array(
			'main' => array(
				'2026-10-05' => $this->row( '2026-10-05', 'main', 101, 1, 0 ),
				'2026-10-06' => $this->row( '2026-10-06', 'main', 102, 0, 0 ),
				'2026-10-07' => $this->row( '2026-10-07', 'main', null, 0, 0 ),
				'2026-10-08' => $this->row( '2026-10-08', 'main', null, 0, 1 ),
			),
			// A stale local calendar must not override the shared schedule.
			'sm' => array( '2026-10-05' => $this->row( '2026-10-05', 'sm', null, 0, 0 ) ),
		);
		$this->items = array(
			101 => array( $this->item( 'Senior bread', 40 ), $this->item( 'Senior dinner', 280, 'dinner' ) ),
			201 => array( $this->item( 'Primary bread', 20 ), $this->item( 'Hidden primary dinner', 240, 'dinner' ) ),
		);
	}
	private function row( string $date, string $type, ?int $id, int $cycle_start, int $iterate ): array {
		return array( 'id' => 1, 'date' => $date, 'school_type' => $type, 'template_id' => $id, 'school' => 'Test school', 'dept' => 'Source department', 'is_cycle_start' => $cycle_start, 'iterate_number' => $iterate );
	}
	private function item( string $name, int $grams, string $meal = 'breakfast' ): array {
		return array( 'dish_name' => $name, 'grams' => $grams, 'meal_type' => $meal, 'section' => 'хлеб', 'recipe_num' => '1', 'price' => 10, 'kcal' => 100, 'protein' => 2, 'fat' => 1, 'carbs' => 20 );
	}
	public function install(): void {
		self::$instance = $this;
		$this->wpdb = new wpdb( $this );
		$this->prefix = 'test_';
		$GLOBALS['wpdb'] = $this->wpdb;
		$GLOBALS['test_transients'] = array();
		$GLOBALS['test_options'] = array();
		$_GET = array();
	}
	public function get_department( string $type ): ?array { return $this->departments[ $type ] ?? null; }
	public function get_enabled_departments(): array { return array_values( array_filter( $this->departments, static fn( array $dept ): bool => ! empty( $dept['is_enabled'] ) ) ); }
	public function get_all_departments(): array { return array_values( $this->departments ); }
	public function get_template( int $id ): ?array { return $this->templates[ $id ] ?? null; }
	public function get_templates( string $type = 'sm' ): array {
		return array_values( array_filter( $this->templates, static fn( array $tpl ): bool => $tpl['school_type'] === $type ) );
	}
	public function get_templates_ordered( string $type ): array {
		return array_column( $this->get_templates( $type ), 'id', 'day_number' );
	}
	public function get_calendar_day( string $date, string $type = 'sm' ): ?array {
		$row = $this->calendar[ $type ][ $date ] ?? null;
		if ( $row === null ) { return null; }
		$tpl = $row['template_id'] === null ? null : $this->get_template( (int) $row['template_id'] );
		return $row + array( 'template_label' => $tpl['label'] ?? null, 'label' => $tpl['label'] ?? null, 'day_number' => $tpl['day_number'] ?? null, 'is_boarding' => $tpl['is_boarding'] ?? null );
	}
	public function get_calendar_month( int $year, int $month, string $type = 'sm' ): array {
		$rows = array();
		foreach ( array_keys( $this->calendar[ $type ] ?? array() ) as $date ) {
			if ( str_starts_with( $date, sprintf( '%04d-%02d-', $year, $month ) ) ) {
				$rows[ $date ] = $this->get_calendar_day( $date, $type );
			}
		}
		return $rows;
	}
	public function get_calendar_year( int $year, string $type ): array {
		$rows = array();
		foreach ( array_keys( $this->calendar[ $type ] ?? array() ) as $date ) {
			if ( str_starts_with( $date, sprintf( '%04d-', $year ) ) ) {
				$rows[ $date ] = $this->get_calendar_day( $date, $type );
			}
		}
		return $rows;
	}
	public function save_calendar_day( string $date, ?int $template_id, ?string $school, ?string $dept, string $type = 'sm', int $is_cycle_start = 0, int $iterate_number = 0 ): void {
		$this->calendar[ $type ][ $date ] = $this->row( $date, $type, $template_id, $is_cycle_start, $iterate_number );
		$this->calendar[ $type ][ $date ]['school'] = $school;
		$this->calendar[ $type ][ $date ]['dept'] = $dept;
	}
	public function delete_calendar_day( string $date, string $type = 'sm' ): void {
		unset( $this->calendar[ $type ][ $date ] );
	}
	public function get_template_items( int $template_id ): array {
		$grouped = array_fill_keys( array( 'breakfast', 'breakfast2', 'lunch', 'afternoon_snack', 'dinner', 'dinner2' ), array() );
		foreach ( $this->items[ $template_id ] ?? array() as $item ) { $grouped[ $item['meal_type'] ][] = $item; }
		return $grouped;
	}
	public function get_org_name(): string { return 'Test school'; }
	public function is_kindergarten(): bool { return false; }
	public function is_camp_period_for_month( string $type, int $year, int $month ): bool { return false; }
	public function is_vacation_day( string $date ): bool {
		foreach ( $this->vacations as $vac ) {
			if ( $date >= $vac['date_from'] && $date <= $vac['date_to'] ) {
				return true;
			}
		}
		return false;
	}
	public function get_vacation_days_for_range( string $from, string $to ): array {
		$days = array();
		foreach ( $this->vacations as $vac ) {
			$cur = new \DateTime( max( $vac['date_from'], $from ) );
			$end = new \DateTime( min( $vac['date_to'], $to ) );
			while ( $cur <= $end ) {
				$days[ $cur->format( 'Y-m-d' ) ] = array( 'label' => $vac['label'] );
				$cur->modify( '+1 day' );
			}
		}
		return $days;
	}
	public function get_effective_holidays( string $from, string $to ): array { return array(); }
}
