<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$atts = shortcode_atts( array( 'type' => '' ), $atts ?? array() );

$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );
$type_labels   = array_combine( array_column( $enabled_depts, 'code' ), array_column( $enabled_depts, 'label' ) );
$req_type = $_GET['meal_type'] ?? '';
$type = $req_type && in_array( $req_type, $valid_types, true ) ? $req_type : ( $atts['type'] && in_array( $atts['type'], $valid_types, true ) ? $atts['type'] : ( $valid_types[0] ?? 'sm' ) );

$org_name = $db->get_org_name();

$today_dt = new \DateTimeImmutable( current_time( 'Y-m-d' ) );

$year  = (int) ( $_GET['meal_y'] ?? $today_dt->format( 'Y' ) );
$month = (int) ( $_GET['meal_m'] ?? $today_dt->format( 'n' ) );

$req_dt = new \DateTimeImmutable( "$year-$month-01" );

$days_in_month = (int) $req_dt->format( 't' );
$first_dow     = (int) $req_dt->format( 'N' );

$cal_rows = array();
$c = $db->get_table_name( 'calendar' );
$t = $db->get_table_name( 'templates' );
global $wpdb;
$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT c.date, c.template_id, mt.day_number, mt.label
	 FROM $c c
	 LEFT JOIN $t mt ON mt.id = c.template_id
	 WHERE c.school_type = %s AND YEAR(c.date) = %d AND MONTH(c.date) = %d",
	$type, $year, $month
), ARRAY_A ) ?: array();
foreach ( $rows as $r ) {
	$cal_rows[ $r['date'] ] = $r;
}

$prev_dt = $req_dt->modify( '-1 month' );
$next_dt = $req_dt->modify( '+1 month' );
$today   = $today_dt->format( 'Y-m-d' );

$month_from    = sprintf( '%04d-%02d-01', $year, $month );
$month_to      = sprintf( '%04d-%02d-%02d', $year, $month, $days_in_month );
$vacation_days = $db->get_vacation_days_for_range( $month_from, $month_to );

$month_names  = array( 1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь' );

$palette = get_option( 'meal_theme_palette', 'retro' );
$layout  = get_option( 'meal_theme_layout', 'classic' );
?>
<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>">
	<div class="meal-container">
		<div class="meal-title"><?php _e( 'Календарь питания', 'meal-menu' ); ?></div>

		<div class="meal-tabs">
			<?php foreach ( $type_labels as $t => $label ): ?>
			<a class="meal-tab<?php echo $type === $t ? ' meal-tab--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'meal_type' => $t, 'meal_y' => $year, 'meal_m' => $month ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<div class="meal-nav-month">
			<a href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $prev_dt->format( 'Y' ), 'meal_m' => $prev_dt->format( 'n' ) ) ) ); ?>">← <?php echo $month_names[ (int) $prev_dt->format( 'n' ) ]; ?></a>
			<h2><?php echo $month_names[ $month ]; ?> <?php echo $year; ?></h2>
			<a href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $next_dt->format( 'Y' ), 'meal_m' => $next_dt->format( 'n' ) ) ) ); ?>"><?php echo $month_names[ (int) $next_dt->format( 'n' ) ]; ?> →</a>
		</div>

		<div class="meal-cal-grid">
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
				$is_vacation = isset( $vacation_days[ $date_str ] );

				$classes = array( 'meal-cal-cell' );
				if ( $is_vacation ) $classes[] = 'vacation';
				elseif ( $has_menu ) $classes[] = 'has-menu';
				elseif ( $is_holiday ) $classes[] = 'holiday';
				elseif ( $is_weekend ) $classes[] = 'weekend';
				if ( $date_str === $today ) $classes[] = 'today';
			?>
			<div class="<?php echo implode( ' ', $classes ); ?>">
				<div class="meal-cal-day"><?php echo $d; ?></div>
				<?php if ( $has_menu ): ?>
				<a class="meal-cal-link meal-menu-trigger" href="#" data-date="<?php echo esc_attr( $date_str ); ?>" data-type="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $entry['label'] ?? __( 'Меню', 'meal-menu' ) ); ?></a>
				<?php elseif ( $is_holiday ): ?>
				<div class="meal-holiday-label"><?php _e( 'Выходной', 'meal-menu' ); ?></div>
				<?php endif; ?>
			</div>
			<?php endfor;

			$last_dow = (int) ( new \DateTimeImmutable( "$year-$month-$days_in_month" ) )->format( 'N' );
			for ( $e = $last_dow + 1; $e <= 7; $e++ ): ?>
			<div class="meal-cal-cell empty"></div>
			<?php endfor; ?>
		</div>

		<div class="meal-legend">
			<div class="meal-legend-item"><div class="meal-legend-dot has-menu"></div> <?php _e( 'Меню опубликовано', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot no-menu"></div> <?php _e( 'Нет данных', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot holiday"></div> <?php _e( 'Выходной / праздник', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot vacation"></div> <?php _e( 'Каникулы', 'meal-menu' ); ?></div>
		</div>
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

	<footer class="meal-footer">
		<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">github.com/igor-blag/web-food</a>
	</footer>
</div>
