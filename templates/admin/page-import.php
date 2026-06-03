<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$tpl_id    = (int) ( $_GET['id'] ?? 0 );
$tpl       = $tpl_id ? $db->get_template( $tpl_id ) : null;
$type      = $tpl['school_type'] ?? 'sm';
$templates = $db->get_templates( $type );

$error_msg = '';
$error_map = array(
	'upload' => __( 'Ошибка загрузки файла.', 'meal-menu' ),
	'format' => __( 'Загрузите файл в формате .xlsx', 'meal-menu' ),
	'empty'  => __( 'Блюда не найдены. Проверьте структуру файла.', 'meal-menu' ),
	'parse'  => __( 'Не удалось прочитать файл. Проверьте формат.', 'meal-menu' ),
	'save'   => __( 'Ошибка при сохранении.', 'meal-menu' ),
);
if ( isset( $_GET['error'] ) && isset( $error_map[ $_GET['error'] ] ) ) {
	$error_msg = $error_map[ $_GET['error'] ];
}

$preview = null;
if ( isset( $_GET['preview'] ) ) {
	$data = get_transient( 'meal_import_preview_' . get_current_user_id() );
	if ( is_array( $data ) ) {
		$preview = $data;
	}
}
?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Импорт меню из Excel', 'meal-menu' ); ?></h1>

	<?php if ( $error_msg ): ?>
	<div class="alert alert-error"><?php echo esc_html( $error_msg ); ?></div>
	<?php endif; ?>

	<?php if ( ! $tpl ): ?>
	<div class="panel">
		<p class="text-muted"><?php _e( 'Выберите шаблон для импорта:', 'meal-menu' ); ?></p>
		<?php foreach ( $templates as $t ): ?>
		<a href="admin.php?page=meal-import&id=<?php echo (int) $t['id']; ?>" class="btn btn-outline btn-sm" style="margin:2px"><?php echo esc_html( $t['label'] ); ?></a>
		<?php endforeach; ?>
	</div>
	<?php else: ?>
	<div class="panel">
		<div class="panel-title"><?php printf( __( 'Импорт в шаблон: %s (%s)', 'meal-menu' ), esc_html( $tpl['label'] ), esc_html( $type ) ); ?></div>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'meal_import_upload' ); ?>
			<input type="hidden" name="action" value="meal_import_upload">
			<input type="hidden" name="template_id" value="<?php echo (int) $tpl_id; ?>">
			<div class="form-group">
				<label for="xlsx"><?php _e( 'Файл .xlsx', 'meal-menu' ); ?></label>
				<input type="file" name="xlsx" id="xlsx" accept=".xlsx" required class="form-control" style="max-width:400px">
			</div>
			<button type="submit" class="btn btn-primary"><?php _e( 'Загрузить и предпросмотреть', 'meal-menu' ); ?></button>
			<a href="admin.php?page=meal-templates&id=<?php echo (int) $tpl_id; ?>" class="btn btn-outline"><?php _e( 'Отмена', 'meal-menu' ); ?></a>
		</form>
	</div>
	<?php endif; ?>

	<?php if ( $preview && is_array( $preview ) ): ?>
	<div class="panel">
		<div class="panel-title"><?php printf( __( 'Предпросмотр: %d блюд', 'meal-menu' ), count( $preview ) ); ?></div>
		<table class="items-table">
			<thead>
				<tr>
					<th><?php _e( 'Приём пищи', 'meal-menu' ); ?></th>
					<th><?php _e( 'Раздел', 'meal-menu' ); ?></th>
					<th><?php _e( 'Блюдо', 'meal-menu' ); ?></th>
					<th><?php _e( 'Выход', 'meal-menu' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $preview as $item ): ?>
				<tr>
					<td><?php echo esc_html( $item['meal_type'] ?? '' ); ?></td>
					<td><?php echo esc_html( $item['section'] ?? '' ); ?></td>
					<td><?php echo esc_html( $item['dish_name'] ?? '' ); ?></td>
					<td><?php echo esc_html( $item['grams'] ?? '' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px">
			<?php wp_nonce_field( 'meal_import_confirm' ); ?>
			<input type="hidden" name="action" value="meal_import_confirm">
			<button type="submit" class="btn btn-primary"><?php _e( 'Подтвердить импорт', 'meal-menu' ); ?></button>
		</form>
	</div>
	<?php endif; ?>
</div>
