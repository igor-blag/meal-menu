<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class DB {

	protected static ?DB $instance = null;
	protected \wpdb $wpdb;
	protected string $prefix;

	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->prefix = $wpdb->prefix . 'meal_';
	}

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function drop_tables(): void {
		global $wpdb;
		$p = $wpdb->prefix . 'meal_';
		$tables = array(
			"{$p}items",
			"{$p}calendar",
			"{$p}templates",
			"{$p}email_tokens",
			"{$p}users",
			"{$p}oc_monitoring",
			"{$p}vacations",
			"{$p}departments",
			"{$p}kitchen_settings",
		);
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
	}

	protected function t( string $table ): string {
		return $this->prefix . $table;
	}

	public function get_templates( string $type = 'sm' ): array {
		$t  = $this->t( 'templates' );
		$sql = $this->wpdb->prepare( "SELECT * FROM $t WHERE school_type = %s ORDER BY day_number", $type );
		return $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
	}

	public function get_template( int $id ): ?array {
		$t   = $this->t( 'templates' );
		$sql = $this->wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id );
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: null;
	}

	public function add_template( string $type ): int {
		$t   = $this->t( 'templates' );
		$sql = $this->wpdb->prepare( "SELECT COALESCE(MAX(day_number), 0) FROM $t WHERE school_type = %s", $type );
		$next_day = (int) $this->wpdb->get_var( $sql ) + 1;
		$this->wpdb->insert(
			$t,
			array(
				'day_number'  => $next_day,
				'school_type' => $type,
				'label'       => "День $next_day",
			),
			array( '%d', '%s', '%s' )
		);
		return $this->wpdb->insert_id;
	}

	public function delete_template( int $id ): void {
		$t   = $this->t( 'templates' );
		$sql = $this->wpdb->prepare( "DELETE FROM $t WHERE id = %d", $id );
		$this->wpdb->query( $sql );
	}

	public function set_template_boarding( int $id, int $is_boarding ): void {
		$t = $this->t( 'templates' );
		$this->wpdb->update(
			$t,
			array( 'is_boarding' => $is_boarding ? 1 : 0 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	public function get_templates_ordered( string $type ): array {
		$t   = $this->t( 'templates' );
		$sql = $this->wpdb->prepare( "SELECT id, day_number FROM $t WHERE school_type = %s ORDER BY day_number ASC", $type );
		$rows = $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
		$map = array();
		foreach ( $rows as $row ) {
			$map[ (int) $row['day_number'] ] = (int) $row['id'];
		}
		return $map;
	}

	public function get_cycle_length( string $type ): int {
		$t   = $this->t( 'templates' );
		$sql = $this->wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE school_type = %s", $type );
		return (int) $this->wpdb->get_var( $sql );
	}

	public function get_template_items( int $template_id ): array {
		$t   = $this->t( 'items' );
		$sql = $this->wpdb->prepare( "SELECT * FROM $t WHERE template_id = %d ORDER BY meal_type, sort_order, id", $template_id );
		$rows = $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();

		$grouped = array(
			'breakfast'       => array(),
			'breakfast2'      => array(),
			'lunch'           => array(),
			'afternoon_snack' => array(),
			'dinner'          => array(),
			'dinner2'         => array(),
		);
		foreach ( $rows as $row ) {
			if ( isset( $grouped[ $row['meal_type'] ] ) ) {
				$grouped[ $row['meal_type'] ][] = $row;
			}
		}
		return $grouped;
	}

	public function save_template_items( int $template_id, array $items ): void {
		$t = $this->t( 'items' );
		$this->wpdb->delete( $t, array( 'template_id' => $template_id ), array( '%d' ) );

		foreach ( $items as $order => $item ) {
			$this->wpdb->insert(
				$t,
				array(
					'template_id' => $template_id,
					'meal_type'   => $item['meal_type'],
					'section'     => $item['section']    ?? null,
					'recipe_num'  => $item['recipe_num'] ?? null,
					'dish_name'   => $item['dish_name']  ?? null,
					'grams'       => isset( $item['grams'] )   ? (float) $item['grams']   : null,
					'price'       => isset( $item['price'] )   ? (float) $item['price']   : null,
					'kcal'        => isset( $item['kcal'] )    ? (float) $item['kcal']    : null,
					'protein'     => isset( $item['protein'] ) ? (float) $item['protein'] : null,
					'fat'         => isset( $item['fat'] )     ? (float) $item['fat']     : null,
					'carbs'       => isset( $item['carbs'] )   ? (float) $item['carbs']   : null,
					'sort_order'  => $order,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d' )
			);
		}
	}

	public function get_calendar_day( string $date, string $type = 'sm' ): ?array {
		$c = $this->t( 'calendar' );
		$t = $this->t( 'templates' );
		$sql = $this->wpdb->prepare(
			"SELECT c.*, t.label AS template_label, t.is_boarding
			 FROM $c c
			 LEFT JOIN $t t ON t.id = c.template_id
			 WHERE c.date = %s AND c.school_type = %s",
			$date,
			$type
		);
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: null;
	}

	public function get_calendar_month( int $year, int $month, string $type = 'sm' ): array {
		$from = sprintf( '%04d-%02d-01', $year, $month );
		$to   = gmdate( 'Y-m-t', strtotime( $from ) );
		$c = $this->t( 'calendar' );
		$t = $this->t( 'templates' );
		$sql = $this->wpdb->prepare(
			"SELECT c.*, t.label AS template_label
			 FROM $c c
			 LEFT JOIN $t t ON t.id = c.template_id
			 WHERE c.date BETWEEN %s AND %s AND c.school_type = %s",
			$from,
			$to,
			$type
		);
		$rows = $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['date'] ] = $row;
		}
		return $result;
	}

	public function get_calendar_range( string $from, string $to, string $type ): array {
		$c = $this->t( 'calendar' );
		$t = $this->t( 'templates' );
		$sql = $this->wpdb->prepare(
			"SELECT c.*, t.label AS template_label
			 FROM $c c
			 LEFT JOIN $t t ON t.id = c.template_id
			 WHERE c.date BETWEEN %s AND %s AND c.school_type = %s",
			$from, $to, $type
		);
		$rows = $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['date'] ] = $row;
		}
		return $result;
	}

	public function get_calendar_year( int $year, string $type ): array {
		$from = sprintf( '%04d-01-01', $year );
		$to   = sprintf( '%04d-12-31', $year );
		$c = $this->t( 'calendar' );
		$t = $this->t( 'templates' );
		$sql = $this->wpdb->prepare(
			"SELECT c.*, t.day_number
			 FROM $c c
			 LEFT JOIN $t t ON t.id = c.template_id
			 WHERE c.date BETWEEN %s AND %s AND c.school_type = %s",
			$from,
			$to,
			$type
		);
		$rows = $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['date'] ] = $row;
		}
		return $result;
	}

	public function save_calendar_day( string $date, ?int $template_id, ?string $school, ?string $dept, string $type = 'sm', int $is_cycle_start = 0, int $iterate_number = 0 ): void {
		$c = $this->t( 'calendar' );
		$existing = $this->get_calendar_day( $date, $type );
		$data = array(
			'template_id'    => $template_id,
			'school'         => $school,
			'dept'           => $dept,
			'is_cycle_start' => $is_cycle_start ? 1 : 0,
			'iterate_number' => $iterate_number ? 1 : 0,
		);
		if ( $existing ) {
			$this->wpdb->update(
				$c,
				$data,
				array( 'date' => $date, 'school_type' => $type ),
				array( '%d', '%s', '%s', '%d', '%d' ),
				array( '%s', '%s' )
			);
		} else {
			$data['date']        = $date;
			$data['school_type'] = $type;
			$this->wpdb->insert(
				$c,
				$data,
				array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}

	public function delete_calendar_day( string $date, string $type = 'sm' ): void {
		$c   = $this->t( 'calendar' );
		$sql = $this->wpdb->prepare( "DELETE FROM $c WHERE date = %s AND school_type = %s", $date, $type );
		$this->wpdb->query( $sql );
	}

	public function assign_cycle( string $start_date, int $start_day, string $type, ?string $school, ?string $dept, ?string $end_date = null, array $workdays = array( 1, 2, 3, 4, 5 ), bool $overwrite = false ): int {
		$templates = $this->get_templates_ordered( $type );
		$cycle_len = count( $templates );
		if ( $cycle_len === 0 ) {
			return 0;
		}

		$keys = array_keys( $templates );
		$idx  = ( $start_day - 1 ) % $cycle_len;

		$cur = new \DateTime( $start_date );
		$end = new \DateTime( $end_date ?? ( $cur->format( 'Y' ) . '-12-31' ) );
		$count = 0;
		$first = true;

		while ( $cur <= $end ) {
			$wday = (int) $cur->format( 'N' );
			$date_str = $cur->format( 'Y-m-d' );
			$existing = $this->get_calendar_day( $date_str, $type );

			$is_scheduled = in_array( $wday, $workdays, true );
			$is_user_holiday = $existing && $existing['template_id'] === null && !$existing['iterate_number'];
			$is_user_workday = $existing && $existing['template_id'] === null && $existing['iterate_number'];
			$has_menu = $existing && $existing['template_id'] !== null;

			if ( ($is_scheduled && !$is_user_holiday) || $is_user_workday ) {
				if ( $overwrite || !$has_menu ) {
					$day_num = $keys[ $idx % $cycle_len ];
					$tpl_id  = $templates[ $day_num ];
					$this->save_calendar_day( $date_str, $tpl_id, $school, $dept, $type, $first ? 1 : 0 );
					$count++;
				}
				$idx++;
				$first = false;
			}
			$cur->modify( '+1 day' );
		}
		return $count;
	}

	public function bulk_save_calendar( array $days, string $type ): void {
		$templates = $this->get_templates_ordered( $type );

		foreach ( $days as $d ) {
			$date    = $d['date']    ?? '';
			$day_num = (int) ( $d['day_num'] ?? 0 );

			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				continue;
			}

			$existing = $this->get_calendar_day( $date, $type );
			$iterate_number = $d['iterate_number'] ?? ( $existing ? $existing['iterate_number'] : 0 );

			if ( $day_num === -1 ) {
				$this->delete_calendar_day( $date, $type );
			} elseif ( $day_num === 0 ) {
				$this->save_calendar_day( $date, null, $d['school'] ?? null, $d['dept'] ?? null, $type, 0, $iterate_number );
			} else {
				if ( $existing && $existing['template_id'] !== null ) {
					continue;
				}
				$tpl_id = $templates[ $day_num ] ?? null;
				if ( $tpl_id ) {
					$this->save_calendar_day(
						$date,
						$tpl_id,
						$d['school'] ?? null,
						$d['dept'] ?? null,
						$type,
						! empty( $d['is_cycle_start'] ) ? 1 : 0,
						$iterate_number
					);
				}
			}
		}
	}

	public function get_kitchen_settings(): array {
		$t   = $this->t( 'kitchen_settings' );
		$sql = "SELECT * FROM $t WHERE id = 1";
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: array(
			'id' => 1,
			'org_name' => '',
			'academic_year_start' => '09-01',
			'academic_year_end' => '05-26',
			'reset_cycle_after_vacation' => 0,
			'tm_approver_position' => '',
			'tm_approver_name' => '',
			'tm_approve_date' => null,
		);
	}

	public function save_kitchen_settings( string $org_name ): void {
		$t = $this->t( 'kitchen_settings' );
		$this->wpdb->update(
			$t,
			array( 'org_name' => $org_name ),
			array( 'id' => 1 ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function save_tm_approver( string $position, string $name ): void {
		$t = $this->t( 'kitchen_settings' );
		$this->wpdb->update(
			$t,
			array(
				'tm_approver_position' => $position,
				'tm_approver_name'     => $name,
			),
			array( 'id' => 1 ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public function save_tm_approve_date( ?string $date ): void {
		$t = $this->t( 'kitchen_settings' );
		$this->wpdb->update(
			$t,
			array( 'tm_approve_date' => $date ?: null ),
			array( 'id' => 1 ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function get_org_name(): string {
		$settings = $this->get_kitchen_settings();
		return $settings['org_name'] ?? '';
	}

	public function save_kitchen_settings_bulk( array $data ): void {
		$t        = $this->t( 'kitchen_settings' );
		$allowed  = array( 'org_name', 'academic_year_start', 'academic_year_end', 'reset_cycle_after_vacation', 'tm_approver_position', 'tm_approver_name', 'tm_approve_date' );
		$update   = array();
		$formats  = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$update[ $key ] = $data[ $key ];
				if ( in_array( $key, array( 'reset_cycle_after_vacation' ), true ) ) {
					$formats[] = '%d';
				} else {
					$formats[] = '%s';
				}
			}
		}
		if ( ! empty( $update ) ) {
			$this->wpdb->update( $t, $update, array( 'id' => 1 ), $formats, array( '%d' ) );
		}
	}

	public function get_all_departments(): array {
		$t   = $this->t( 'departments' );
		$sql = "SELECT * FROM $t ORDER BY sort_order, id";
		return $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
	}

	public function get_enabled_departments(): array {
		$t   = $this->t( 'departments' );
		$sql = "SELECT * FROM $t WHERE is_enabled = 1 ORDER BY sort_order, id";
		return $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
	}

	public function get_department( string $code ): ?array {
		$t   = $this->t( 'departments' );
		$sql = $this->wpdb->prepare( "SELECT * FROM $t WHERE code = %s", $code );
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: null;
	}

	public function get_department_by_id( int $id ): ?array {
		$t   = $this->t( 'departments' );
		$sql = $this->wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id );
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: null;
	}

	public function save_department( int $id, array $data ): void {
		$t       = $this->t( 'departments' );
		$allowed = array( 'label', 'label_short', 'dept_name', 'is_enabled', 'is_builtin',
			'is_boarding', 'workdays', 'publish_xlsx', 'file_suffix', 'sort_order', 'note', 'ignore_vacations', 'merged_with' );
		$update  = array();
		$formats = array();
		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $allowed, true ) ) {
				$update[ $k ] = $v;
				if ( in_array( $k, array( 'is_enabled', 'is_builtin', 'is_boarding', 'publish_xlsx', 'sort_order', 'ignore_vacations' ), true ) ) {
					$formats[] = '%d';
				} else {
					$formats[] = '%s';
				}
			}
		}
		if ( ! empty( $update ) ) {
			$this->wpdb->update( $t, $update, array( 'id' => $id ), $formats, array( '%d' ) );
		}
	}

	public function add_department( string $code, string $label, string $file_suffix = '' ): int {
		$t       = $this->t( 'departments' );
		$max_sql = "SELECT COALESCE(MAX(sort_order), 0) FROM $t";
		$max     = (int) $this->wpdb->get_var( $max_sql );
		$this->wpdb->insert(
			$t,
			array(
				'code'        => $code,
				'label'       => $label,
				'label_short' => $label,
				'is_enabled'  => 1,
				'is_builtin'  => 0,
				'file_suffix' => $file_suffix,
				'sort_order'  => $max + 10,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%d' )
		);
		return $this->wpdb->insert_id;
	}

	public function delete_department( int $id ): void {
		$t   = $this->t( 'departments' );
		$sql = $this->wpdb->prepare( "DELETE FROM $t WHERE id = %d AND is_builtin = 0", $id );
		$this->wpdb->query( $sql );
	}

	public function resort_departments( array $order ): void {
		$t = $this->t( 'departments' );
		foreach ( $order as $index => $id ) {
			$this->wpdb->update(
				$t,
				array( 'sort_order' => ( $index + 1 ) * 10 ),
				array( 'id' => (int) $id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	public function get_workdays( string $code ): array {
		$dept = $this->get_department( $code );
		if ( ! $dept ) {
			return array( 1, 2, 3, 4, 5 );
		}
		return array_map( 'intval', explode( ',', $dept['workdays'] ) );
	}

	public function sync_templates_to_cycle_length( string $type, int $desired ): array {
		$templates = $this->get_templates( $type );
		$current   = count( $templates );
		$result    = array( 'added' => 0, 'removed' => 0, 'kept' => $current );

		if ( $desired === $current ) {
			return $result;
		}

		if ( $desired > $current ) {
			for ( $i = 0; $i < $desired - $current; $i++ ) {
				$this->add_template( $type );
				$result['added']++;
			}
			$result['kept'] = $current;
		} else {
			$to_remove = $current - $desired;
			$reversed  = array_reverse( $templates );
			foreach ( $reversed as $tpl ) {
				if ( $to_remove <= 0 ) {
					break;
				}
				$items    = $this->get_template_items( (int) $tpl['id'] );
				$has_items = false;
				foreach ( $items as $meal_items ) {
					if ( ! empty( $meal_items ) ) {
						$has_items = true;
						break;
					}
				}
				if ( ! $has_items ) {
					$this->delete_template( (int) $tpl['id'] );
					$to_remove--;
					$result['removed']++;
				}
			}
			$result['kept'] = $current - $result['removed'];
		}
		return $result;
	}

	public function get_academic_year_settings(): array {
		$t   = $this->t( 'kitchen_settings' );
		$sql = "SELECT academic_year_start, academic_year_end, reset_cycle_after_vacation FROM $t WHERE id = 1";
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: array(
			'academic_year_start'       => '09-01',
			'academic_year_end'         => '05-31',
			'reset_cycle_after_vacation' => 0,
		);
	}

	public function save_academic_year_settings( string $start, string $end, int $reset ): void {
		$t = $this->t( 'kitchen_settings' );
		$this->wpdb->update(
			$t,
			array(
				'academic_year_start'       => $start,
				'academic_year_end'         => $end,
				'reset_cycle_after_vacation' => $reset ? 1 : 0,
			),
			array( 'id' => 1 ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);
	}

	public function get_vacations( string $academic_year ): array {
		$t   = $this->t( 'vacations' );
		$sql = $this->wpdb->prepare( "SELECT * FROM $t WHERE academic_year = %s ORDER BY date_from", $academic_year );
		return $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();
	}

	public function add_vacation( string $academic_year, string $label, string $date_from, string $date_to, ?string $actual_date = null ): int {
		$t = $this->t( 'vacations' );
		$data = array(
			'academic_year' => $academic_year,
			'label'         => $label,
			'date_from'     => $date_from,
			'date_to'       => $date_to,
		);
		$fmts = array( '%s', '%s', '%s', '%s' );
		if ( $actual_date !== null ) {
			$data['actual_date'] = $actual_date;
			$fmts[] = '%s';
		}
		$this->wpdb->insert( $t, $data, $fmts );
		return $this->wpdb->insert_id;
	}

	public function update_vacation( int $id, string $label, string $date_from, string $date_to, ?string $actual_date = null ): void {
		$t = $this->t( 'vacations' );
		$data = array(
			'label'     => $label,
			'date_from' => $date_from,
			'date_to'   => $date_to,
		);
		$fmts = array( '%s', '%s', '%s' );
		if ( $actual_date !== null ) {
			$data['actual_date'] = $actual_date;
			$fmts[] = '%s';
		}
		$this->wpdb->update( $t, $data, array( 'id' => $id ), $fmts, array( '%d' ) );
	}

	public function delete_vacation( int $id ): void {
		$t   = $this->t( 'vacations' );
		$sql = $this->wpdb->prepare( "DELETE FROM $t WHERE id = %d", $id );
		$this->wpdb->query( $sql );
	}

	public function is_vacation_day( string $date ): bool {
		$t   = $this->t( 'vacations' );
		$sql = $this->wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE %s BETWEEN date_from AND date_to", $date );
		return (int) $this->wpdb->get_var( $sql ) > 0;
	}

	public function get_academic_year_for_date( string $date ): string {
		$settings = $this->get_academic_year_settings();
		$year     = (int) ( new \DateTime( $date ) )->format( 'Y' );
		$start_this_year = $year . '-' . $settings['academic_year_start'];
		if ( $date >= $start_this_year ) {
			return $year . '-' . ( $year + 1 );
		}
		return ( $year - 1 ) . '-' . $year;
	}

	public function get_current_period( string $type, ?string $date = null ): array {
		$date     = $date ?? current_time( 'Y-m-d' );
		$settings = $this->get_academic_year_settings();
		$dept     = $this->get_department( $type );
		$academic_year = $this->get_academic_year_for_date( $date );

		list( $year_start, $year_end ) = explode( '-', $academic_year, 2 );
		$ay_from = $year_start . '-' . $settings['academic_year_start'];
		$ay_to   = $year_end . '-' . $settings['academic_year_end'];

		if ( $dept && ! empty( $dept['ignore_vacations'] ) ) {
			return array(
				'from'  => $ay_from,
				'to'    => $ay_to,
				'label' => 'Учебный год ' . $academic_year,
			);
		}

		$vacations = $this->get_vacations( $academic_year );

		if ( empty( $vacations ) ) {
			return array(
				'from'  => $ay_from,
				'to'    => $ay_to,
				'label' => 'Учебный год ' . $academic_year,
			);
		}

		$periods      = array();
		$period_start = $ay_from;
		$period_num   = 1;

		foreach ( $vacations as $vac ) {
			$vac_from   = $vac['date_from'];
			$vac_to     = $vac['date_to'];
			$period_end = ( new \DateTime( $vac_from ) )->modify( '-1 day' )->format( 'Y-m-d' );

			if ( $period_end >= $period_start ) {
				$periods[] = array(
					'from'  => $period_start,
					'to'    => $period_end,
					'label' => $this->get_period_label( $period_num, count( $vacations ) ),
				);
				$period_num++;
			}
			$period_start = ( new \DateTime( $vac_to ) )->modify( '+1 day' )->format( 'Y-m-d' );
		}

		if ( $period_start <= $ay_to ) {
			$periods[] = array(
				'from'  => $period_start,
				'to'    => $ay_to,
				'label' => $this->get_period_label( $period_num, count( $vacations ) ),
			);
		}

		foreach ( $periods as $p ) {
			if ( $date >= $p['from'] && $date <= $p['to'] ) {
				return $p;
			}
		}

		foreach ( $periods as $p ) {
			if ( $p['from'] > $date ) {
				return $p;
			}
		}

		return ! empty( $periods ) ? end( $periods ) : array(
			'from'  => $ay_from,
			'to'    => $ay_to,
			'label' => 'Учебный год ' . $academic_year,
		);
	}

	public function get_period_label( int $num, int $total_vacations ): string {
		if ( $total_vacations >= 3 ) {
			$names = array( 1 => 'I четверть', 2 => 'II четверть', 3 => 'III четверть', 4 => 'IV четверть' );
			return $names[ $num ] ?? "$num-й период";
		}
		if ( $total_vacations === 2 ) {
			$names = array( 1 => 'I триместр', 2 => 'II триместр', 3 => 'III триместр' );
			return $names[ $num ] ?? "$num-й период";
		}
		if ( $total_vacations === 1 ) {
			$names = array( 1 => 'I полугодие', 2 => 'II полугодие' );
			return $names[ $num ] ?? "$num-й период";
		}
		return "$num-й период";
	}

	public function get_vacation_days_for_range( string $from, string $to ): array {
		$t   = $this->t( 'vacations' );
		$sql = $this->wpdb->prepare(
			"SELECT * FROM $t WHERE date_from <= %s AND date_to >= %s ORDER BY date_from",
			$to,
			$from
		);
		$vacations = $this->wpdb->get_results( $sql, ARRAY_A ) ?: array();

		$days = array();
		foreach ( $vacations as $vac ) {
			$is_holiday = $vac['date_from'] === $vac['date_to'];
			$actual_date = ! empty( $vac['actual_date'] ) ? $vac['actual_date'] : ( $is_holiday ? $vac['date_from'] : null );
			$cur = new \DateTime( max( $vac['date_from'], $from ) );
			$end = new \DateTime( min( $vac['date_to'], $to ) );
			while ( $cur <= $end ) {
				$days[ $cur->format( 'Y-m-d' ) ] = array(
					'label'       => $vac['label'],
					'is_holiday'  => $is_holiday,
					'actual_date' => $actual_date,
				);
				$cur->modify( '+1 day' );
			}
		}
		return $days;
	}

	public function get_oc_monitoring(): array {
		$t   = $this->t( 'oc_monitoring' );
		$sql = "SELECT * FROM $t WHERE id = 1";
		$row = $this->wpdb->get_row( $sql, ARRAY_A );
		return $row ?: array(
			'school_name' => '', 'report_date' => '', 's1_url' => '',
			's2_hotline' => '', 's2_chat_url' => '', 's2_forum_url' => '',
			's3_diet1_type' => '', 's3_diet1_url' => '',
			's3_diet2_type' => '', 's3_diet2_url' => '',
			's3_diet3_type' => '', 's3_diet3_url' => '',
			's3_diet4_type' => '', 's3_diet4_url' => '',
			's4_survey_url' => '', 's4_results_url' => '',
			's5_page_url' => '', 's5_materials_url' => '',
			's6_acts_url' => '', 's6_photos_url' => '',
			's7_waste_level' => 'none',
		);
	}

	public function save_oc_monitoring( array $d ): void {
		$t   = $this->t( 'oc_monitoring' );
		$row = $this->wpdb->get_row( "SELECT id FROM $t WHERE id = 1" );

		$data = array(
			'school_name'    => $d['school_name'] ?? '',
			'report_date'    => $d['report_date']  ?? null,
			's1_url'         => $d['s1_url']        ?? '',
			's2_hotline'     => $d['s2_hotline']    ?? '',
			's2_chat_url'    => $d['s2_chat_url']   ?? '',
			's2_forum_url'   => $d['s2_forum_url']  ?? '',
			's3_diet1_type'  => $d['s3_diet1_type'] ?? '',
			's3_diet1_url'   => $d['s3_diet1_url']  ?? '',
			's3_diet2_type'  => $d['s3_diet2_type'] ?? '',
			's3_diet2_url'   => $d['s3_diet2_url']  ?? '',
			's3_diet3_type'  => $d['s3_diet3_type'] ?? '',
			's3_diet3_url'   => $d['s3_diet3_url']  ?? '',
			's3_diet4_type'  => $d['s3_diet4_type'] ?? '',
			's3_diet4_url'   => $d['s3_diet4_url']  ?? '',
			's4_survey_url'  => $d['s4_survey_url'] ?? '',
			's4_results_url' => $d['s4_results_url'] ?? '',
			's5_page_url'    => $d['s5_page_url']   ?? '',
			's5_materials_url' => $d['s5_materials_url'] ?? '',
			's6_acts_url'    => $d['s6_acts_url']   ?? '',
			's6_photos_url'  => $d['s6_photos_url'] ?? '',
			's7_waste_level' => $d['s7_waste_level'] ?? 'none',
		);

		if ( $row ) {
			$this->wpdb->update( $t, $data, array( 'id' => 1 ) );
		} else {
			$data['id'] = 1;
			$this->wpdb->insert( $t, $data );
		}
	}

	public function get_table_name( string $table ): string {
		return $this->prefix . $table;
	}

	public function count_filled_workdays_ahead( string $from_date ): int {
		$dt    = new \DateTimeImmutable( $from_date );
		$dt    = $dt->modify( '+1 day' );
		$count = 0;
		$limit = 60;
		$c     = $this->t( 'calendar' );

		for ( $i = 0; $i < $limit; $i++ ) {
			$dow     = (int) $dt->format( 'N' );
			$date_str = $dt->format( 'Y-m-d' );

			if ( $dow <= 5 && ! $this->is_vacation_day( $date_str ) ) {
				$sql  = $this->wpdb->prepare( "SELECT template_id FROM $c WHERE date = %s AND school_type = 'sm' LIMIT 1", $date_str );
				$tpl  = $this->wpdb->get_var( $sql );
				if ( ! $tpl ) {
					break;
				}
				$count++;
			}
			$dt = $dt->modify( '+1 day' );
		}
		return $count;
	}
}
