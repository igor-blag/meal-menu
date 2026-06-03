<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$atts = shortcode_atts( array( 'type' => '' ), $atts ?? array() );

$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );
$type_labels   = array_combine( array_column( $enabled_depts, 'code' ), array_column( $enabled_depts, 'label' ) );
$type = $atts['type'] && in_array( $atts['type'], $valid_types, true ) ? $atts['type'] : ( $valid_types[0] ?? 'sm' );

$org_name = $db->get_org_name();

$today_dt      = new \DateTimeImmutable( current_time( 'Y-m-d' ) );
$first_allowed = new \DateTimeImmutable( $today_dt->format( 'Y-m-01' ) );

$year  = (int) ( $_GET['y'] ?? $today_dt->format( 'Y' ) );
$month = (int) ( $_GET['m'] ?? $today_dt->format( 'n' ) );

$req_dt = new \DateTimeImmutable( "$year-$month-01" );
if ( $req_dt < $first_allowed ) {
	$req_dt = $first_allowed;
	$year  = (int) $req_dt->format( 'Y' );
	$month = (int) $req_dt->format( 'n' );
}

$days_in_month = (int) $req_dt->format( 't' );
$first_dow     = (int) $req_dt->format( 'N' );

$cal_rows = array();
$c = $db->get_table_name( 'calendar' );
$t = $db->get_table_name( 'templates' );
global $wpdb;
$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT c.date, c.template_id, mt.day_number
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
	<header class="meal-header">
		<div class="meal-logo"><?php echo esc_html( $org_name ?: __( 'Организация питания', 'meal-menu' ) ); ?></div>
		<nav class="meal-nav">
			<a class="meal-nav-link" href="<?php echo esc_url( add_query_arg( array( 'type' => $type ), remove_query_arg( array( 'y', 'm', 'day' ) ) ) ); ?>"><?php _e( 'Календарь', 'meal-menu' ); ?></a>
			<a class="meal-nav-link" href="<?php echo esc_url( add_query_arg( array( 'type' => $type, 'page' => 'meal_menu' ), remove_query_arg( array( 'y', 'm', 'day' ) ) ) ); ?>"><?php _e( 'Типовое меню', 'meal-menu' ); ?></a>
			<a class="meal-nav-link" href="<?php echo esc_url( add_query_arg( 'page', 'meal_oc' ) ); ?>"><?php _e( 'Общ. контроль', 'meal-menu' ); ?></a>
		</nav>
	</header>

	<div class="meal-container">
		<div class="meal-title"><?php _e( 'Календарь питания', 'meal-menu' ); ?></div>

		<div class="meal-tabs">
			<?php foreach ( $type_labels as $t => $label ): ?>
			<a class="meal-tab<?php echo $type === $t ? ' meal-tab--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'type' => $t, 'y' => $year, 'm' => $month ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<div class="meal-nav-month">
			<?php if ( $prev_dt >= $first_allowed ): ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'y' => $prev_dt->format( 'Y' ), 'm' => $prev_dt->format( 'n' ) ) ) ); ?>">← <?php echo $month_names[ (int) $prev_dt->format( 'n' ) ]; ?></a>
			<?php else: ?>
			<a class="meal-disabled">←</a>
			<?php endif; ?>
			<h2><?php echo $month_names[ $month ]; ?> <?php echo $year; ?></h2>
			<a href="<?php echo esc_url( add_query_arg( array( 'y' => $next_dt->format( 'Y' ), 'm' => $next_dt->format( 'n' ) ) ) ); ?>"><?php echo $month_names[ (int) $next_dt->format( 'n' ) ]; ?> →</a>
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
				<a class="meal-cal-link" href="<?php echo esc_url( add_query_arg( array( 'date' => $date_str, 'type' => $type, 'page' => 'meal_day' ) ) ); ?>"><?php _e( 'Меню', 'meal-menu' ); ?></a>
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

	<footer class="meal-footer">
		<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">github.com/igor-blag/web-food</a>
	</footer>
</div>
