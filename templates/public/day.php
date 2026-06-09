<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$atts = shortcode_atts( array( 'date' => '', 'type' => '' ), $atts ?? array() );

$db   = \Meal_Menu\DB::instance();
$date = $atts['date'] ?: ( $_GET['meal_date'] ?? '' );
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! strtotime( $date ) ) {
	echo '<div class="meal-wrapper"><p>' . __( 'Дата не указана.', 'meal-menu' ) . '</p></div>';
	return;
}

$enabled_depts = $db->get_enabled_departments();
$org_name      = $db->get_org_name();
$valid_types   = array_column( $enabled_depts, 'code' );
$req_type = $_GET['meal_type'] ?? '';
$type = $req_type && in_array( $req_type, $valid_types, true ) ? $req_type : ( $atts['type'] && in_array( $atts['type'], $valid_types, true ) ? $atts['type'] : ( $valid_types[0] ?? 'sm' ) );

$dept = $db->get_department( $type );
$real_type = ( $dept && ! empty( $dept['merged_with'] ) ) ? $dept['merged_with'] : $type;

$dt = new \DateTimeImmutable( $date );

$c = $db->get_table_name( 'calendar' );
$t = $db->get_table_name( 'templates' );
global $wpdb;
$cal = $wpdb->get_row( $wpdb->prepare(
	"SELECT c.*, mt.day_number, mt.label, mt.is_boarding
	 FROM $c c
	 JOIN $t mt ON mt.id = c.template_id
	 WHERE c.date = %s AND c.school_type = %s AND c.template_id IS NOT NULL",
	$date, $real_type
), ARRAY_A );

if ( ! $cal ) {
	$no_menu = true;
} else {
	$no_menu = false;
	$ti = $db->get_table_name( 'items' );
	$item_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $ti WHERE template_id = %d ORDER BY meal_type, sort_order, id",
		$cal['template_id']
	), ARRAY_A ) ?: array();
	$by_meal = array();
	foreach ( $item_rows as $it ) {
		if ( empty( $it['dish_name'] ) ) continue;
		$by_meal[ $it['meal_type'] ][] = $it;
	}
}

$meal_names = array(
	'breakfast' => 'Завтрак', 'breakfast2' => 'Второй завтрак', 'lunch' => 'Обед',
	'afternoon_snack' => 'Полдник', 'dinner' => 'Ужин', 'dinner2' => 'Ужин (2-й)',
);

$day_names     = array( '', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье' );
$month_names_g = array( 1=>'января',2=>'февраля',3=>'марта',4=>'апреля',5=>'мая',6=>'июня',7=>'июля',8=>'августа',9=>'сентября',10=>'октября',11=>'ноября',12=>'декабря' );
$dow     = (int) $dt->format( 'N' );
$day_str = $day_names[ $dow ] . ', ' . (int) $dt->format( 'd' ) . ' ' . $month_names_g[ (int) $dt->format( 'n' ) ] . ' ' . $dt->format( 'Y' ) . ' г.';

$upload_dir = wp_upload_dir();
$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
$files_url  = $upload_dir['baseurl'] . '/meal-menu';
$suffix     = ( $type !== 'main' ) ? "-{$type}" : '';
$xls_name   = $date . $suffix . '.xlsx';
$has_xls    = file_exists( $meal_dir . '/' . $xls_name );

$palette = get_option( 'meal_theme_palette', 'retro' );
$layout  = get_option( 'meal_theme_layout', 'classic' );
?>
<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>">
	<header class="meal-header">
		<div class="meal-logo"><?php echo esc_html( $org_name ?: __( 'Организация питания', 'meal-menu' ) ); ?></div>
	</header>

	<div class="meal-container">
		<div class="meal-title"><?php _e( 'Меню на день', 'meal-menu' ); ?></div>
		<div class="meal-date"><?php echo esc_html( $day_str ); ?></div>

		<div style="margin-bottom:16px">
			<a class="meal-back-link" href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $dt->format( 'Y' ), 'meal_m' => $dt->format( 'n' ) ), remove_query_arg( 'meal_date' ) ) ); ?>">← <?php _e( 'Назад к календарю', 'meal-menu' ); ?></a>
		</div>

		<?php if ( $has_xls ): ?>
		<div style="margin-bottom:16px">
			<a class="meal-download-btn" href="<?php echo esc_url( $files_url . '/' . $xls_name ); ?>" download>↓ <?php _e( 'Скачать меню (.xlsx)', 'meal-menu' ); ?></a>
		</div>
		<?php endif; ?>

		<?php if ( $no_menu ): ?>
		<div class="meal-notice"><?php _e( 'Меню на этот день не опубликовано.', 'meal-menu' ); ?></div>
		<?php else:
			foreach ( $meal_names as $mtype => $mname ):
				if ( empty( $by_meal[ $mtype ] ) ) continue;
				if ( in_array( $mtype, array( 'afternoon_snack', 'dinner', 'dinner2' ), true ) && empty( $cal['is_boarding'] ) ) continue;
		?>
		<div class="meal-block">
			<div class="meal-block-title"><?php echo esc_html( $mname ); ?></div>
			<table class="meal-table">
				<thead>
					<tr>
						<th style="width:42%"><?php _e( 'Блюдо', 'meal-menu' ); ?></th>
						<th style="width:8%"><?php _e( 'Выход, г', 'meal-menu' ); ?></th>
						<th style="width:9%"><?php _e( 'Цена, ₽', 'meal-menu' ); ?></th>
						<th style="width:9%"><?php _e( 'Ккал', 'meal-menu' ); ?></th>
						<th style="width:8%"><?php _e( 'Белки', 'meal-menu' ); ?></th>
						<th style="width:8%"><?php _e( 'Жиры', 'meal-menu' ); ?></th>
						<th style="width:8%"><?php _e( 'Углев.', 'meal-menu' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$prev_section = null;
				foreach ( $by_meal[ $mtype ] as $it ):
					if ( $it['section'] && $it['section'] !== $prev_section ):
						$prev_section = $it['section'];
				?>
					<tr class="section-row"><td colspan="7"><?php echo esc_html( $it['section'] ); ?></td></tr>
				<?php endif; ?>
					<tr>
						<td><?php echo esc_html( $it['dish_name'] ?? '' ); ?></td>
						<td class="num"><?php echo $it['grams'] !== null ? number_format( $it['grams'], 1, ',', '' ) : ''; ?></td>
						<td class="num"><?php echo $it['price'] !== null ? number_format( $it['price'], 2, ',', '' ) : ''; ?></td>
						<td class="num"><?php echo $it['kcal'] !== null ? number_format( $it['kcal'], 1, ',', '' ) : ''; ?></td>
						<td class="num"><?php echo $it['protein'] !== null ? number_format( $it['protein'], 1, ',', '' ) : ''; ?></td>
						<td class="num"><?php echo $it['fat'] !== null ? number_format( $it['fat'], 1, ',', '' ) : ''; ?></td>
						<td class="num"><?php echo $it['carbs'] !== null ? number_format( $it['carbs'], 1, ',', '' ) : ''; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach; endif; ?>
	</div>

	<footer class="meal-footer">
		<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">github.com/igor-blag/web-food</a>
	</footer>
</div>
