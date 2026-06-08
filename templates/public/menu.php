<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$atts = shortcode_atts( array( 'type' => '' ), $atts ?? array() );

$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$org_name      = $db->get_org_name();
$valid_types   = array_column( $enabled_depts, 'code' );
$type_labels   = array_combine( array_column( $enabled_depts, 'code' ), array_column( $enabled_depts, 'label' ) );
$req_type = $_GET['meal_type'] ?? '';
$type = $req_type && in_array( $req_type, $valid_types, true ) ? $req_type : ( $atts['type'] && in_array( $atts['type'], $valid_types, true ) ? $atts['type'] : ( $valid_types[0] ?? 'sm' ) );

$templates = $db->get_templates( $type );
$cycle_len = count( $templates );

$sel_id = (int) ( $_GET['meal_day'] ?? ( $templates[0]['id'] ?? 0 ) );
$items  = array();
$sel_tpl = null;
foreach ( $templates as $t ) {
	if ( (int) $t['id'] === $sel_id ) {
		$sel_tpl = $t;
		break;
	}
}

if ( $sel_tpl ) {
	$ti = $db->get_table_name( 'items' );
	global $wpdb;
	$item_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $ti WHERE template_id = %d ORDER BY meal_type, sort_order, id",
		$sel_tpl['id']
	), ARRAY_A ) ?: array();
	foreach ( $item_rows as $it ) {
		$items[ $it['meal_type'] ][] = $it;
	}
}

$meal_names = array(
	'breakfast' => 'Завтрак', 'breakfast2' => 'Второй завтрак', 'lunch' => 'Обед',
	'afternoon_snack' => 'Полдник', 'dinner' => 'Ужин', 'dinner2' => 'Ужин (2-й)',
);

$palette = get_option( 'meal_theme_palette', 'retro' );
$layout  = get_option( 'meal_theme_layout', 'classic' );
?>
<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>">
	<header class="meal-header">
		<div class="meal-logo"><?php echo esc_html( $org_name ?: __( 'Организация питания', 'meal-menu' ) ); ?></div>
	</header>

	<div class="meal-container">
		<div class="meal-title"><?php printf( __( 'Типовое меню — %d-дневный цикл', 'meal-menu' ), $cycle_len ); ?></div>

		<?php if ( empty( $templates ) ): ?>
		<div class="meal-notice"><?php _e( 'Шаблоны меню ещё не созданы.', 'meal-menu' ); ?></div>
		<?php else: ?>

		<div class="meal-tabs">
			<?php foreach ( $templates as $t ): ?>
			<a class="meal-tab<?php echo (int) $t['id'] === $sel_id ? ' meal-tab--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'meal_day', (int) $t['id'] ) ); ?>"><?php echo esc_html( $t['label'] ); ?></a>
			<?php endforeach; ?>
		</div>

		<div style="margin:16px 0">
			<a class="meal-back-link" href="<?php echo esc_url( remove_query_arg( 'meal_day' ) ); ?>">← <?php _e( 'Назад к календарю', 'meal-menu' ); ?></a>
		</div>

		<?php foreach ( $meal_names as $mtype => $mname ):
			if ( empty( $items[ $mtype ] ) ) continue;
			if ( in_array( $mtype, array( 'afternoon_snack', 'dinner', 'dinner2' ), true ) && empty( $sel_tpl['is_boarding'] ) ) continue;
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
				foreach ( $items[ $mtype ] as $it ):
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
		<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<footer class="meal-footer">
		<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">github.com/igor-blag/web-food</a>
	</footer>
</div>
