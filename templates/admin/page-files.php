<?php if ( ! defined( 'ABSPATH' ) ) exit; $db = \Meal_Menu\DB::instance(); $upload_dir = wp_upload_dir(); $meal_dir = $upload_dir['basedir'] . '/meal-menu'; $departments = $db->get_enabled_departments(); ?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Скачать файлы', 'meal-menu' ); ?></h1>

	<?php foreach ( $departments as $dept ):
		$suffix  = $dept['file_suffix'];
		$pattern = $meal_dir . '/*' . $suffix . '.xlsx';
		$matches = glob( $pattern ) ?: array();
		if ( empty( $matches ) ) continue;
		rsort( $matches );
	?>
	<div class="panel">
		<div class="panel-title"><?php echo esc_html( $dept['label'] ); ?></div>
		<table class="menu-table">
			<thead>
				<tr>
					<th><?php _e( 'Файл', 'meal-menu' ); ?></th>
					<th><?php _e( 'Размер', 'meal-menu' ); ?></th>
					<th><?php _e( 'Дата', 'meal-menu' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array_slice( $matches, 0, 50 ) as $filepath ): ?>
				<tr>
					<td><?php echo esc_html( basename( $filepath ) ); ?></td>
					<td><?php echo esc_html( size_format( filesize( $filepath ) ) ); ?></td>
					<td><?php echo esc_html( gmdate( 'Y-m-d H:i', filemtime( $filepath ) ) ); ?></td>
					<td><a href="<?php echo esc_url( $upload_dir['baseurl'] . '/meal-menu/' . basename( $filepath ) ); ?>" class="btn btn-outline btn-sm" download>&#x2193;</a></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endforeach; ?>

	<?php if ( empty( $departments ) ): ?>
	<div class="alert alert-error"><?php _e( 'Нет сгенерированных файлов.', 'meal-menu' ); ?></div>
	<?php endif; ?>
</div>
