<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$id      = (int) ( $_GET['id'] ?? 0 );
$is_camp = ! empty( $_GET['camp'] );
$tpl     = $is_camp ? $db->get_camp_template( $id ) : $db->get_template( $id );
if ( ! $tpl ) {
	echo '<div class="wrap meal-menu-wrap"><div class="alert alert-error">' . __( 'Шаблон не найден', 'meal-menu' ) . '</div></div>';
	return;
}

$dept_info      = $db->get_department( $tpl['school_type'] );
$forced_boarding = $is_camp ? ( $dept_info && ! empty( $dept_info['camp_is_boarding'] ) ) : ( $dept_info && ! empty( $dept_info['is_boarding'] ) );

$saved = isset( $_GET['saved'] );
$items = $is_camp ? $db->get_camp_template_items( $id ) : $db->get_template_items( $id );
$is_boarding = ! empty( $tpl['is_boarding'] );

$section_meta = array(
	'breakfast'       => array( 'label' => 'Завтрак',   'hint' => 'гор.блюдо, напиток, хлеб…',           'boarding' => false ),
	'breakfast2'      => array( 'label' => 'Завтрак 2', 'hint' => 'фрукты и т.д.',                        'boarding' => false ),
	'lunch'           => array( 'label' => 'Обед',       'hint' => 'закуска, 1 блюдо, 2 блюдо, гарнир…', 'boarding' => false ),
	'afternoon_snack' => array( 'label' => 'Полдник',   'hint' => 'фрукты, напиток…',                     'boarding' => true ),
	'dinner'          => array( 'label' => 'Ужин',       'hint' => 'гор.блюдо, напиток…',                 'boarding' => true ),
	'dinner2'         => array( 'label' => 'Ужин 2',     'hint' => 'кефир, выпечка…',                     'boarding' => true ),
);
?>
<div class="wrap meal-menu-wrap">
	<?php if ( $saved ): ?>
	<div class="alert alert-success"><?php _e( 'Шаблон сохранён.', 'meal-menu' ); ?></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['imported'] ) ): ?>
	<div class="alert alert-success">&#x2713; <?php _e( 'Меню успешно импортировано из Excel.', 'meal-menu' ); ?></div>
	<?php endif; ?>

	<div class="flex items-center justify-between mb-2">
		<h1 class="page-title" style="border:none;margin:0">
			<?php printf( __( 'Редактор: %s', 'meal-menu' ), esc_html( $tpl['label'] ) ); ?>
			<?php if ( $is_camp ): ?>
			<span style="font-size:.72em;background:#fff3cd;color:#856404;padding:2px 8px;border-radius:3px;margin-left:8px;vertical-align:middle"><?php _e( 'Летний лагерь', 'meal-menu' ); ?></span>
			<?php endif; ?>
		</h1>
		<div class="flex gap-2">
			<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $tpl['school_type'] ); ?><?php echo $is_camp ? '&camp=1' : ''; ?>" class="btn btn-outline btn-sm">&larr; <?php _e( 'Все шаблоны', 'meal-menu' ); ?></a>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="tpl-form">
		<?php wp_nonce_field( 'meal_save_template' ); ?>
		<input type="hidden" name="action" value="meal_save_template">
		<input type="hidden" name="template_id" value="<?php echo (int) $id; ?>">
		<?php if ( $is_camp ): ?>
		<input type="hidden" name="is_camp" value="1">
		<?php endif; ?>

		<div class="panel" style="margin-bottom:12px;padding:12px 16px">
			<label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:500">
				<input type="checkbox" name="is_boarding" id="boardingCheck" value="1"
					<?php echo $is_boarding ? 'checked' : ''; ?>
					<?php echo $forced_boarding ? 'disabled checked' : ''; ?>
					style="width:16px;height:16px">
				<?php if ( $forced_boarding ): ?>
				<input type="hidden" name="is_boarding" value="1">
				<?php endif; ?>
				<?php _e( 'Интернат (расширенное питание: Полдник, Ужин, Ужин 2)', 'meal-menu' ); ?>
				<?php if ( $forced_boarding ): ?>
				<span style="font-size:.78em;color:var(--wp-muted);margin-left:8px">(<?php _e( 'задано в настройках отделения', 'meal-menu' ); ?>)</span>
				<?php endif; ?>
			</label>
		</div>

		<?php foreach ( $section_meta as $meal => $meta ):
			$rows = $items[ $meal ] ?? array();
			$rows[] = array(); $rows[] = array();
			$hidden = ( $meta['boarding'] && ! $is_boarding ) ? ' style="display:none"' : '';
			$boarding_class = $meta['boarding'] ? ' boarding-section' : '';
		?>
		<div class="meal-section<?php echo $boarding_class; ?>"<?php echo $hidden; ?>>
			<div class="meal-section-title">
				<?php echo esc_html( $meta['label'] ); ?>
				<span style="font-weight:normal;opacity:.6;font-size:.85em;margin-left:8px"><?php echo esc_html( $meta['hint'] ); ?></span>
			</div>
			<table class="items-table" id="tbl-<?php echo $meal; ?>">
				<thead>
					<tr>
						<th style="width:100px"><?php _e( 'Раздел', 'meal-menu' ); ?></th>
						<th style="width:90px"><?php _e( '№ рец.', 'meal-menu' ); ?></th>
						<th><?php _e( 'Блюдо', 'meal-menu' ); ?></th>
						<th style="width:60px"><?php _e( 'Выход', 'meal-menu' ); ?></th>
						<th style="width:65px"><?php _e( 'Цена', 'meal-menu' ); ?></th>
						<th style="width:60px"><?php _e( 'Ккал', 'meal-menu' ); ?></th>
						<th style="width:55px"><?php _e( 'Белки', 'meal-menu' ); ?></th>
						<th style="width:55px"><?php _e( 'Жиры', 'meal-menu' ); ?></th>
						<th style="width:55px"><?php _e( 'Углев.', 'meal-menu' ); ?></th>
						<th style="width:32px"></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $row ): ?>
					<tr>
						<td><input type="text" name="<?php echo $meal; ?>[section][]" value="<?php echo esc_attr( $row['section'] ?? '' ); ?>"></td>
						<td><input type="text" name="<?php echo $meal; ?>[recipe_num][]" value="<?php echo esc_attr( $row['recipe_num'] ?? '' ); ?>"></td>
						<td><input type="text" name="<?php echo $meal; ?>[dish_name][]" value="<?php echo esc_attr( $row['dish_name'] ?? '' ); ?>"></td>
						<td><input type="number" step="0.1" name="<?php echo $meal; ?>[grams][]" value="<?php echo esc_attr( $row['grams'] ?? '' ); ?>"></td>
						<td><input type="number" step="0.01" name="<?php echo $meal; ?>[price][]" value="<?php echo esc_attr( $row['price'] ?? '' ); ?>"></td>
						<td><input type="number" step="0.01" name="<?php echo $meal; ?>[kcal][]" value="<?php echo esc_attr( $row['kcal'] ?? '' ); ?>"></td>
						<td><input type="number" step="0.01" name="<?php echo $meal; ?>[protein][]" value="<?php echo esc_attr( $row['protein'] ?? '' ); ?>"></td>
						<td><input type="number" step="0.01" name="<?php echo $meal; ?>[fat][]" value="<?php echo esc_attr( $row['fat'] ?? '' ); ?>"></td>
						<td><input type="number" step="0.01" name="<?php echo $meal; ?>[carbs][]" value="<?php echo esc_attr( $row['carbs'] ?? '' ); ?>"></td>
						<td><button type="button" class="del-row" title="<?php esc_attr_e( 'Удалить строку', 'meal-menu' ); ?>">✕</button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<button type="button" class="btn btn-outline btn-sm add-row-btn" data-target="tbl-<?php echo $meal; ?>" data-meal="<?php echo $meal; ?>">
				+ <?php _e( 'Добавить блюдо', 'meal-menu' ); ?>
			</button>
		</div>
		<?php endforeach; ?>

		<?php if ( ! $is_camp && $tpl['school_type'] === 'sm' ):
			$stored_date = $db->get_kitchen_settings()['tm_approve_date'] ?? '';
		?>
		<div class="panel" style="margin-top:12px;padding:12px 16px">
			<div style="font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:var(--wp-muted);margin-bottom:8px">
				<?php _e( 'Типовое меню — дата утверждения', 'meal-menu' ); ?>
			</div>
			<div style="display:flex;align-items:center;gap:12px">
				<input type="date" name="tm_approve_date" value="<?php echo esc_attr( $stored_date ); ?>"
					style="padding:6px 10px;font-size:.9rem;border:1px solid var(--wp-border);border-radius:var(--wp-radius);color:var(--wp-text)">
				<span style="font-size:.82rem;color:var(--wp-muted)"><?php _e( 'Будет записана в шапку tm-файла при сохранении', 'meal-menu' ); ?></span>
			</div>
		</div>
		<?php endif; ?>

		<div class="flex gap-2 mt-3">
			<button type="submit" class="btn btn-primary"><?php _e( 'Сохранить шаблон', 'meal-menu' ); ?></button>
			<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $tpl['school_type'] ); ?><?php echo $is_camp ? '&camp=1' : ''; ?>" class="btn btn-outline"><?php _e( 'Отмена', 'meal-menu' ); ?></a>
		</div>
	</form>
</div>

<script>
document.getElementById('boardingCheck').addEventListener('change', function() {
	var show = this.checked;
	document.querySelectorAll('.boarding-section').forEach(function(el) {
		el.style.display = show ? '' : 'none';
	});
});

document.addEventListener('click', function(e) {
	if (e.target.classList.contains('del-row')) {
		e.target.closest('tr').remove();
	}
});

document.querySelectorAll('.add-row-btn').forEach(function(btn) {
	btn.addEventListener('click', function() {
		var meal  = btn.dataset.meal;
		var tbody = document.querySelector('#' + btn.dataset.target + ' tbody');
		var tr    = document.createElement('tr');
		var fields = ['section','recipe_num','dish_name'];
		var nums   = ['grams','price','kcal','protein','fat','carbs'];
		var html   = '';
		fields.forEach(function(f) {
			html += '<td><input type="text" name="' + meal + '[' + f + '][]"></td>';
		});
		nums.forEach(function(f) {
			html += '<td><input type="number" step="0.01" name="' + meal + '[' + f + '][]"></td>';
		});
		html += '<td><button type="button" class="del-row" title="<?php esc_attr_e( 'Удалить', 'meal-menu' ); ?>">✕</button></td>';
		tr.innerHTML = html;
		tbody.appendChild(tr);
		tr.querySelector('input').focus();
	});
});
</script>
