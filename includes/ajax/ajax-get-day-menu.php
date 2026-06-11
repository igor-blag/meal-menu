<?php
defined( 'ABSPATH' ) || exit;

$is_camp = ! empty( $_GET['camp'] );

if ( $is_camp ) {
	$c  = $db->get_table_name( 'camp_calendar' );
	$t  = $db->get_table_name( 'camp_templates' );
	$ti = $db->get_table_name( 'camp_items' );
} else {
	$c  = $db->get_table_name( 'calendar' );
	$t  = $db->get_table_name( 'templates' );
	$ti = $db->get_table_name( 'items' );
}
global $wpdb;

$cal = $wpdb->get_row( $wpdb->prepare(
	"SELECT c.*, mt.day_number, mt.label, mt.is_boarding
	 FROM $c c
	 JOIN $t mt ON mt.id = c.template_id
	 WHERE c.date = %s AND c.school_type = %s AND c.template_id IS NOT NULL",
	$date, $type
), ARRAY_A );

if ( ! $cal ) {
	wp_send_json( array( 'ok' => false, 'html' => '<p>' . __( 'Меню на этот день не опубликовано.', 'meal-menu' ) . '</p>' ) );
}

$item_rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $ti WHERE template_id = %d ORDER BY meal_type, sort_order, id",
	$cal['template_id']
), ARRAY_A ) ?: array();

$by_meal = array();
foreach ( $item_rows as $it ) {
	if ( empty( $it['dish_name'] ) ) continue;
	$by_meal[ $it['meal_type'] ][] = $it;
}

$meal_names = array(
	'breakfast' => __( 'Завтрак', 'meal-menu' ),
	'breakfast2' => __( 'Второй завтрак', 'meal-menu' ),
	'lunch' => __( 'Обед', 'meal-menu' ),
	'afternoon_snack' => __( 'Полдник', 'meal-menu' ),
	'dinner' => __( 'Ужин', 'meal-menu' ),
	'dinner2' => __( 'Ужин (2-й)', 'meal-menu' ),
);

$dt = new \DateTimeImmutable( $date );
$day_names = array( '', __( 'Понедельник', 'meal-menu' ), __( 'Вторник', 'meal-menu' ), __( 'Среда', 'meal-menu' ), __( 'Четверг', 'meal-menu' ), __( 'Пятница', 'meal-menu' ), __( 'Суббота', 'meal-menu' ), __( 'Воскресенье', 'meal-menu' ) );
$month_names_g = array( 1=>__( 'января', 'meal-menu' ), 2=>__( 'февраля', 'meal-menu' ), 3=>__( 'марта', 'meal-menu' ), 4=>__( 'апреля', 'meal-menu' ), 5=>__( 'мая', 'meal-menu' ), 6=>__( 'июня', 'meal-menu' ), 7=>__( 'июля', 'meal-menu' ), 8=>__( 'августа', 'meal-menu' ), 9=>__( 'сентября', 'meal-menu' ), 10=>__( 'октября', 'meal-menu' ), 11=>__( 'ноября', 'meal-menu' ), 12=>__( 'декабря', 'meal-menu' ) );
$dow = (int) $dt->format( 'N' );
$day_str = $day_names[ $dow ] . ', ' . (int) $dt->format( 'd' ) . ' ' . $month_names_g[ (int) $dt->format( 'n' ) ] . ' ' . $dt->format( 'Y' ) . ' г.';

$palette = get_option( 'meal_theme_palette', 'retro' );
$layout  = get_option( 'meal_theme_layout', 'classic' );

ob_start();
?>
<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>" style="background:transparent">
	<div class="meal-date" style="margin-bottom:16px"><?php echo esc_html( $day_str ); ?></div>
	<?php foreach ( $meal_names as $mtype => $mname ):
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
			<?php foreach ( $by_meal[ $mtype ] as $it ): ?>
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
	<?php endforeach; ?>
</div>
<?php
$html = ob_get_clean();

wp_send_json( array(
	'ok'   => true,
	'html' => $html,
) );
