<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	public function __construct() {
		add_shortcode( 'meal_calendar', array( $this, 'shortcode_calendar' ) );
		add_shortcode( 'meal_day', array( $this, 'shortcode_day' ) );
	}

	public function shortcode_calendar( array $atts = array(), string $content = '' ): string {
		$atts = shortcode_atts( array(
			'type'    => '',
			'palette' => '',
			'layout'  => '',
			'show_oc' => true,
			'camp'    => null,
		), $atts );
		$db   = DB::instance();

		$enabled_depts = $db->get_enabled_departments();
		$valid_types   = array_column( $enabled_depts, 'code' );

		$req_type   = $_GET['meal_type'] ?? '';
		$is_camp    = $atts['camp'] === '1' || $atts['camp'] === true || $atts['camp'] === 1 || isset( $_GET['meal_camp'] );
		$req_camp   = isset( $_GET['meal_camp'] ) ? ( $_GET['meal_camp'] === '1' ) : $is_camp;

		$type = $req_type && in_array( $req_type, $valid_types, true ) ? $req_type : ( $atts['type'] && in_array( $atts['type'], $valid_types, true ) ? $atts['type'] : ( $valid_types[0] ?? 'sm' ) );

		if ( ! $req_camp && $db->is_camp_period_for_month( $type, (int) ( $_GET['meal_y'] ?? current_time( 'Y' ) ), (int) ( $_GET['meal_m'] ?? current_time( 'n' ) ) ) ) {
			$any_normal = false;
			foreach ( $enabled_depts as $dep ) {
				if ( ! $db->is_camp_period_for_month( $dep['code'], (int) ( $_GET['meal_y'] ?? current_time( 'Y' ) ), (int) ( $_GET['meal_m'] ?? current_time( 'n' ) ) ) ) {
					$any_normal = true;
					break;
				}
			}
			if ( ! $any_normal ) {
				$req_camp = true;
			}
		}

		$today_dt = new \DateTimeImmutable( current_time( 'Y-m-d' ) );
		$year  = (int) ( $_GET['meal_y'] ?? $today_dt->format( 'Y' ) );
		$month = (int) ( $_GET['meal_m'] ?? $today_dt->format( 'n' ) );

		$palette = $atts['palette'] ?: get_option( 'meal_theme_palette', 'retro' );
		$layout  = $atts['layout'] ?: get_option( 'meal_theme_layout', 'classic' );
		$show_oc = filter_var( $atts['show_oc'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>" id="meal-calendar-root">
			<div class="meal-container">
				<div class="meal-title"><?php _e( 'Календарь питания', 'meal-menu' ); ?></div>
				<div id="meal-calendar-body"><?php echo self::render_calendar_body( $type, $year, $month, $req_camp ); ?></div>
				<?php if ( $show_oc && ! $db->is_kindergarten() ) { echo self::render_oc_content(); } ?>
			</div>

			<div id="meal-modal" class="meal-modal-overlay" style="display:none">
				<div class="meal-modal-dialog">
					<div class="meal-modal-header">
						<span class="meal-modal-title" id="meal-modal-title"></span>
						<button class="meal-modal-close" id="meal-modal-close">&times;</button>
					</div>
					<div class="meal-modal-body" id="meal-modal-body">
						<div class="meal-modal-loader"><?php _e( 'Загрузка…', 'meal-menu' ); ?></div>
					</div>
				</div>
			</div>

			<footer class="meal-footer" style="text-align:right;font-size:10px">
				<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">meal-menu@gh</a>
			</footer>
		</div>
		<?php
		return ob_get_clean();
	}

	public function shortcode_day( array $atts = array(), string $content = '' ): string {
		ob_start();
		$atts = shortcode_atts( array( 'date' => '', 'type' => '' ), $atts );
		require MEAL_MENU_DIR . 'templates/public/day.php';
		return ob_get_clean();
	}

	public static function invalidate_day_menu_cache(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_meal_day_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_meal_day_%'" );
	}

	public static function invalidate_oc_cache(): void {
		delete_transient( 'meal_oc_content' );
	}

	public static function invalidate_calendar_cache(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_meal_cal_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_meal_cal_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_meal_day_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_meal_day_%'" );
	}

	public static function render_calendar_body( string $type, int $year, int $month, bool $is_camp = false ): string {
		$cache_key = 'meal_cal_' . sanitize_key( $type ) . '_' . $year . '_' . $month . '_' . ( $is_camp ? '1' : '0' );
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$db = DB::instance();

		if ( $is_camp ) {
			$enabled_depts = $db->get_enabled_departments();
			$camp_types    = array();
			foreach ( $enabled_depts as $dep ) {
				if ( ! empty( $dep['has_summer_camp'] ) ) {
					$camp_types[] = $dep['code'];
				}
			}
			if ( empty( $camp_types ) ) {
				$camp_types = array( $type );
			}
			$valid_types = $camp_types;
			$type_labels = array_combine( $camp_types, array_map( function( $c ) {
				return 'Летний лагерь';
			}, $camp_types ) );
			$dept = $db->get_department( $type );
			$real_type = $type;

			$cal_rows = array();
			$c = $db->get_table_name( 'camp_calendar' );
			$t = $db->get_table_name( 'camp_templates' );
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.date, c.template_id, mt.day_number, mt.label
				 FROM $c c
				 LEFT JOIN $t mt ON mt.id = c.template_id
				 WHERE c.school_type = %s AND c.date >= %s AND c.date <= %s",
				$real_type,
				sprintf( '%04d-%02d-01', $year, $month ),
				gmdate( 'Y-m-t', strtotime( sprintf( '%04d-%02d-01', $year, $month ) ) )
			), ARRAY_A ) ?: array();
			foreach ( $rows as $r ) {
				$cal_rows[ $r['date'] ] = $r;
			}
		} else {
			$enabled_depts = $db->get_enabled_departments();
			$valid_types   = array_column( $enabled_depts, 'code' );
			$type_labels   = array_combine( array_column( $enabled_depts, 'code' ), array_column( $enabled_depts, 'label' ) );

			$filtered_labels = array();
			foreach ( $type_labels as $t => $label ) {
				if ( ! $db->is_camp_period_for_month( $t, $year, $month ) ) {
					$filtered_labels[ $t ] = $label;
				}
			}
			if ( ! empty( $filtered_labels ) ) {
				$type_labels = $filtered_labels;
				$valid_types = array_keys( $filtered_labels );
			}

			$dept = $db->get_department( $type );
			$real_type = ( $dept && ! empty( $dept['merged_with'] ) ) ? $dept['merged_with'] : $type;

			$cal_rows = array();
			$c = $db->get_table_name( 'calendar' );
			$t = $db->get_table_name( 'templates' );
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.date, c.template_id, mt.day_number, mt.label
				 FROM $c c
				 LEFT JOIN $t mt ON mt.id = c.template_id
				 WHERE c.school_type = %s AND c.date >= %s AND c.date <= %s",
				$real_type,
				sprintf( '%04d-%02d-01', $year, $month ),
				gmdate( 'Y-m-t', strtotime( sprintf( '%04d-%02d-01', $year, $month ) ) )
			), ARRAY_A ) ?: array();
			foreach ( $rows as $r ) {
				$cal_rows[ $r['date'] ] = $r;
			}
		}

		$today_dt = new \DateTimeImmutable( current_time( 'Y-m-d' ) );
		$req_dt   = new \DateTimeImmutable( "$year-$month-01" );
		$days_in_month = (int) $req_dt->format( 't' );
		$first_dow     = (int) $req_dt->format( 'N' );

		$prev_dt = $req_dt->modify( '-1 month' );
		$next_dt = $req_dt->modify( '+1 month' );
		$today   = $today_dt->format( 'Y-m-d' );

		$month_from  = sprintf( '%04d-%02d-01', $year, $month );
		$month_to    = sprintf( '%04d-%02d-%02d', $year, $month, $days_in_month );
		$vacation_days = $db->is_kindergarten() ? array() : $db->get_vacation_days_for_range( $month_from, $month_to );
		if ( $dept && ! empty( $dept['ignore_vacations'] ) ) {
			$vacation_days = array();
		}
		$holidays = $db->get_effective_holidays( $month_from, $month_to );

		$month_names = array( 1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь' );

		if ( ! $is_camp && empty( $type_labels ) ) {
			return self::render_calendar_body( $type, $year, $month, true );
		}

		$show_camp_tab = false;
		if ( ! $is_camp ) {
			foreach ( $enabled_depts as $dep ) {
				if ( ! empty( $dep['has_summer_camp'] ) && $db->is_camp_period_for_month( $dep['code'], $year, $month ) ) {
					$show_camp_tab = true;
					break;
				}
			}
		}

		ob_start();
		?>
		<div class="meal-tabs">
			<?php foreach ( $type_labels as $t => $label ): ?>
			<a class="meal-tab<?php echo ( ! $is_camp && $type === $t ) ? ' meal-tab--active' : ''; ?>" data-type="<?php echo esc_attr( $t ); ?>" data-year="<?php echo $year; ?>" data-month="<?php echo $month; ?>" data-camp="0" href="<?php echo esc_url( add_query_arg( array( 'meal_type' => $t, 'meal_y' => $year, 'meal_m' => $month ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<?php if ( $show_camp_tab ): ?>
			<a class="meal-tab<?php echo $is_camp ? ' meal-tab--active' : ''; ?>" data-type="<?php echo esc_attr( $type ); ?>" data-year="<?php echo $year; ?>" data-month="<?php echo $month; ?>" data-camp="1" href="<?php echo esc_url( add_query_arg( array( 'meal_type' => $type, 'meal_y' => $year, 'meal_m' => $month, 'meal_camp' => '1' ) ) ); ?>"><?php _e( 'Летний лагерь', 'meal-menu' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="meal-nav-month">
			<a href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $prev_dt->format( 'Y' ), 'meal_m' => $prev_dt->format( 'n' ) ) ) ); ?>" data-year="<?php echo $prev_dt->format( 'Y' ); ?>" data-month="<?php echo $prev_dt->format( 'n' ); ?>">← <?php echo $month_names[ (int) $prev_dt->format( 'n' ) ]; ?></a>
			<h2><?php echo $month_names[ $month ]; ?> <?php echo $year; ?></h2>
			<a href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $next_dt->format( 'Y' ), 'meal_m' => $next_dt->format( 'n' ) ) ) ); ?>" data-year="<?php echo $next_dt->format( 'Y' ); ?>" data-month="<?php echo $next_dt->format( 'n' ); ?>"><?php echo $month_names[ (int) $next_dt->format( 'n' ) ]; ?> →</a>
		</div>

		<div class="meal-cal-scroll"><div class="meal-cal-grid">
			<?php foreach ( array( 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) as $h ): ?>
			<div class="meal-cal-head"><?php echo $h; ?></div>
			<?php endforeach; ?>

			<?php for ( $e = 1; $e < $first_dow; $e++ ): ?>
			<div class="meal-cal-cell empty"></div>
			<?php endfor;

			for ( $d = 1; $d <= $days_in_month; $d++ ):
				$date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $d );
				$dow        = (int) ( new \DateTimeImmutable( $date_str ) )->format( 'N' );
				$is_weekend = $dow >= 6;
				$entry      = $cal_rows[ $date_str ] ?? null;
				$has_menu   = $entry && $entry['template_id'] !== null;
				$is_holiday = $entry && $entry['template_id'] === null;

				$vac_day = $vacation_days[ $date_str ] ?? null;
				$is_vacation = (bool) $vac_day;
				$is_holiday_entry = isset( $holidays[ $date_str ] );

				$classes = array( 'meal-cal-cell' );
				if ( $is_vacation ) $classes[] = 'vacation';
				elseif ( $has_menu ) $classes[] = 'has-menu';
				elseif ( $is_holiday_entry ) $classes[] = 'holiday';
				elseif ( $is_holiday ) $classes[] = 'holiday';
				elseif ( $is_weekend ) $classes[] = 'weekend';
				if ( $date_str === $today ) $classes[] = 'today';
			?>
			<div class="<?php echo implode( ' ', $classes ); ?>">
				<div class="meal-cal-day"><?php echo $d; ?></div>
				<?php if ( $has_menu ): ?>
				<a class="meal-cal-link meal-menu-trigger" href="#" data-date="<?php echo esc_attr( $date_str ); ?>" data-type="<?php echo esc_attr( $real_type ?? $type ); ?>" data-camp="<?php echo $is_camp ? '1' : '0'; ?>"><?php echo esc_html( $entry['label'] ?? __( 'Меню', 'meal-menu' ) ); ?></a>
				<?php elseif ( $is_holiday ): ?>
				<div class="meal-holiday-label"><?php _e( 'Выходной', 'meal-menu' ); ?></div>
				<?php endif; ?>
			</div>
			<?php endfor;

			$last_dow = (int) ( new \DateTimeImmutable( "$year-$month-$days_in_month" ) )->format( 'N' );
			for ( $e = $last_dow + 1; $e <= 7; $e++ ): ?>
			<div class="meal-cal-cell empty"></div>
			<?php endfor; ?>
		</div></div>

		<div class="meal-legend">
			<div class="meal-legend-item"><div class="meal-legend-dot has-menu"></div> <?php _e( 'Меню опубликовано', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot no-menu"></div> <?php _e( 'Нет данных', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot holiday"></div> <?php _e( 'Выходной / праздник', 'meal-menu' ); ?></div>
			<?php if ( ! $db->is_kindergarten() ): ?>
			<div class="meal-legend-item"><div class="meal-legend-dot vacation"></div> <?php _e( 'Каникулы', 'meal-menu' ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		set_transient( $cache_key, $html, HOUR_IN_SECONDS * 12 );
		return $html;
	}

	public static function render_oc_content(): string {
		$cached = get_transient( 'meal_oc_content' );
		if ( $cached !== false ) {
			return $cached;
		}

		$db  = DB::instance();
		$oc  = $db->get_oc_monitoring();

		$waste_labels = array( 'none' => '', '20' => '&#60;&#160;20%', '30' => '20–30%', '40' => '30–40%', '50' => '&#62;&#160;50%' );

		$has_any = false;
		foreach ( array( 's1_url', 's2_hotline', 's2_chat_url', 's2_forum_url',
			's3_diet1_type', 's3_diet1_url', 's3_diet2_type', 's3_diet2_url',
			's3_diet3_type', 's3_diet3_url', 's3_diet4_type', 's3_diet4_url',
			's4_survey_url', 's4_results_url', 's5_page_url', 's5_materials_url',
			's6_acts_url', 's6_photos_url', 's7_waste_level' ) as $k ) {
			if ( ! empty( $oc[ $k ] ) ) { $has_any = true; break; }
		}
		if ( ! $has_any ) {
			set_transient( 'meal_oc_content', '', HOUR_IN_SECONDS * 12 );
			return '';
		}

		ob_start();
		?>
		<details class="meal-oc-spoiler" style="margin-top:24px">
			<summary class="meal-oc-summary" style="font-size:.9rem;text-transform:uppercase;letter-spacing:.1em;color:var(--meal-primary);cursor:pointer;padding:8px 0;border-bottom:2px solid var(--meal-primary);margin-bottom:16px"><?php _e( 'Общественный контроль питания', 'meal-menu' ); ?></summary>

			<?php if ( $oc['school_name'] ): ?>
			<p style="margin-bottom:16px;font-size:.9rem;color:var(--meal-muted)"><?php echo esc_html( $oc['school_name'] ); ?></p>
			<?php endif; ?>

			<?php foreach ( array(
				's1_url'      => array( 'title' => 'Раздел 1. Положение и приказ о создании комиссии', 'type' => 'url' ),
				's2_hotline'  => array( 'title' => 'Раздел 2. Формы интерактивного взаимодействия', 'type' => 'section2' ),
				's3_diet1_type' => array( 'title' => 'Раздел 3. Лечебные/диетические меню', 'type' => 'section3' ),
				's4_survey_url' => array( 'title' => 'Раздел 4. Анкетирование', 'type' => 'section4' ),
				's5_page_url'   => array( 'title' => 'Раздел 5. Информация о здоровом питании', 'type' => 'section5' ),
				's6_acts_url'   => array( 'title' => 'Раздел 6. Результаты контрольных мероприятий', 'type' => 'section6' ),
				's7_waste_level'=> array( 'title' => 'Раздел 7. Оценка пищевых отходов', 'type' => 'section7' ),
			) as $key => $section ):
				$show = false;
				switch ( $section['type'] ) {
					case 'url':
						$show = ! empty( $oc[ $key ] );
						break;
					case 'section2':
						$show = ! empty( $oc['s2_hotline'] ) || ! empty( $oc['s2_chat_url'] ) || ! empty( $oc['s2_forum_url'] );
						break;
					case 'section3':
						for ( $i = 1; $i <= 4; $i++ ) {
							if ( ! empty( $oc[ "s3_diet{$i}_type" ] ) || ! empty( $oc[ "s3_diet{$i}_url" ] ) ) { $show = true; break; }
						}
						break;
					case 'section4':
						$show = ! empty( $oc['s4_survey_url'] ) || ! empty( $oc['s4_results_url'] );
						break;
					case 'section5':
						$show = ! empty( $oc['s5_page_url'] ) || ! empty( $oc['s5_materials_url'] );
						break;
					case 'section6':
						$show = ! empty( $oc['s6_acts_url'] ) || ! empty( $oc['s6_photos_url'] );
						break;
					case 'section7':
						$show = ! empty( $oc['s7_waste_level'] ) && $oc['s7_waste_level'] !== 'none';
						break;
				}
				if ( ! $show ) continue;
			?>
			<div class="meal-block" style="border-left:4px solid var(--meal-primary);padding-left:20px">
				<div class="meal-block-title" style="background:transparent;color:var(--meal-heading);padding-left:0"><?php echo esc_html( $section['title'] ); ?></div>
				<?php
				if ( $section['type'] === 'url' ): ?>
					<a href="<?php echo esc_url( $oc[ $key ] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Перейти к документу', 'meal-menu' ); ?></a>
				<?php elseif ( $section['type'] === 'section2' ): ?>
					<?php if ( $oc['s2_hotline'] ): ?><p style="margin-bottom:4px"><strong><?php _e( 'Горячая линия:', 'meal-menu' ); ?></strong> <?php echo esc_html( $oc['s2_hotline'] ); ?></p><?php endif; ?>
					<?php if ( $oc['s2_chat_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s2_chat_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Чат для обратной связи', 'meal-menu' ); ?></a></p><?php endif; ?>
					<?php if ( $oc['s2_forum_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s2_forum_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Форум / обратная связь', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php elseif ( $section['type'] === 'section3' ): ?>
					<?php for ( $i = 1; $i <= 4; $i++ ):
						$t = $oc[ "s3_diet{$i}_type" ] ?? '';
						$u = $oc[ "s3_diet{$i}_url" ] ?? '';
						if ( ! $t && ! $u ) continue;
					?><p style="margin-bottom:4px"><?php echo esc_html( $t ); ?>: <?php if ( $u ): ?><a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'скачать', 'meal-menu' ); ?></a><?php endif; ?></p>
					<?php endfor; ?>
				<?php elseif ( $section['type'] === 'section4' ): ?>
					<?php if ( $oc['s4_survey_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s4_survey_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Пройти анкету', 'meal-menu' ); ?></a></p><?php endif; ?>
					<?php if ( $oc['s4_results_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s4_results_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Результаты анкетирования', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php elseif ( $section['type'] === 'section5' ): ?>
					<?php if ( $oc['s5_page_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s5_page_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Страница о здоровом питании', 'meal-menu' ); ?></a></p><?php endif; ?>
					<?php if ( $oc['s5_materials_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s5_materials_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Материалы', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php elseif ( $section['type'] === 'section6' ): ?>
					<?php if ( $oc['s6_acts_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s6_acts_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Акты контроля', 'meal-menu' ); ?></a></p><?php endif; ?>
					<?php if ( $oc['s6_photos_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s6_photos_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Фотоматериалы', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php elseif ( $section['type'] === 'section7' ): ?>
					<p><?php _e( 'Уровень пищевых отходов:', 'meal-menu' ); ?> <?php echo $waste_labels[ $oc['s7_waste_level'] ] ?? ''; ?></p>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</details>
		<?php
		$html = ob_get_clean();
		set_transient( 'meal_oc_content', $html, HOUR_IN_SECONDS * 12 );
		return $html;
	}
}
