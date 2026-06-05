<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$valid_types   = array_column( $enabled_depts, 'code' );
$type_labels   = array_combine(
	array_column( $enabled_depts, 'code' ),
	array_column( $enabled_depts, 'label' )
);
$type = isset( $_GET['type'] ) && in_array( $_GET['type'], $valid_types, true ) ? $_GET['type'] : ( $valid_types[0] ?? 'sm' );

$templates = $db->get_templates( $type );
$cycle_len = count( $templates );

$day_nums = array_column( $templates, 'day_number' );
$gaps     = array();
for ( $i = 0; $i < count( $day_nums ) - 1; $i++ ) {
	for ( $g = (int) $day_nums[ $i ] + 1; $g < (int) $day_nums[ $i + 1 ]; $g++ ) {
		$gaps[] = $g;
	}
}

$cycle_title = $cycle_len > 0 ? $cycle_len . '-дневный цикл' : 'Нет шаблонов';

$upload_dir = wp_upload_dir();
$tm_file    = $upload_dir['basedir'] . '/meal-menu/tm' . current_time( 'Y' ) . '-sm.xlsx';
$has_tm     = file_exists( $tm_file );
?>
<div class="wrap meal-menu-wrap">
	<div class="flex items-center justify-between mb-2">
		<h1 class="page-title" style="border:none;margin:0"><?php echo esc_html( $cycle_title ); ?></h1>
		<div class="flex gap-2">
			<?php if ( $type === 'sm' && $has_tm ): ?>
			<a href="<?php echo esc_url( $upload_dir['baseurl'] . '/meal-menu/tm' . current_time( 'Y' ) . '-sm.xlsx' ); ?>" class="btn btn-outline btn-sm" download>&#x2193; tm-файл</a>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
				<?php wp_nonce_field( 'meal_add_template' ); ?>
				<input type="hidden" name="action" value="meal_add_template">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
				<button type="submit" class="btn btn-primary btn-sm">+ <?php _e( 'Добавить день', 'meal-menu' ); ?></button>
			</form>
		</div>
	</div>

	<div class="tab-bar">
		<?php foreach ( $type_labels as $t => $label ): ?>
		<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $t ); ?>"
		   class="tab-item<?php echo $type === $t ? ' active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<div id="dropzone" class="dropzone">
		<div class="dropzone-inner">
			<span class="dropzone-icon">&#x21E9;</span>
			<span class="dropzone-text"><?php _e( 'Перетащите XLSX-файлы меню сюда', 'meal-menu' ); ?></span>
			<span class="dropzone-hint"><?php _e( 'или нажмите для выбора (можно выбрать несколько файлов)', 'meal-menu' ); ?></span>
			<input type="file" id="dropzone-file" multiple accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display:none">
		</div>
		<div id="dropzone-progress" class="dropzone-progress" style="display:none">
			<div class="dropzone-progress-bar" id="dropzone-progress-bar"></div>
			<div id="dropzone-status"></div>
		</div>
	</div>

	<?php if ( ! empty( $gaps ) ): ?>
	<div class="alert alert-error" style="margin-bottom:16px">
		<?php _e( 'В цикле пропущены шаблоны:', 'meal-menu' ); ?> <strong>№<?php echo implode( ', №', array_map( 'esc_html', $gaps ) ); ?></strong>.
		<?php _e( 'Дни с такими номерами не будут назначаться в календаре.', 'meal-menu' ); ?>
	</div>
	<?php endif; ?>

	<?php if ( $cycle_len === 0 ): ?>
	<div class="alert alert-error"><?php _e( 'Шаблоны не добавлены. Нажмите «+ Добавить день» чтобы начать.', 'meal-menu' ); ?></div>
	<?php else: ?>
	<div class="panel">
		<table class="menu-table">
			<thead>
				<tr>
					<th style="width:50px">№</th>
					<th><?php _e( 'Название', 'meal-menu' ); ?></th>
					<th style="width:110px"><?php _e( 'Завтрак', 'meal-menu' ); ?></th>
					<th style="width:110px"><?php _e( 'Завтрак 2', 'meal-menu' ); ?></th>
					<th style="width:110px"><?php _e( 'Обед', 'meal-menu' ); ?></th>
					<th style="width:120px"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $t ):
					$items = $db->get_template_items( (int) $t['id'] );
					$c1 = count( $items['breakfast'] );
					$c2 = count( $items['breakfast2'] );
					$c3 = count( $items['lunch'] );
				?>
				<tr>
					<td class="center"><?php echo (int) $t['day_number']; ?></td>
					<td>
						<?php echo esc_html( $t['label'] ); ?>
						<?php if ( $t['is_boarding'] ): ?>
							<span style="font-size:.72em;background:#e8f0fe;color:#1a56db;padding:1px 5px;border-radius:3px;margin-left:6px;vertical-align:middle">ИНТЕРНАТ</span>
						<?php endif; ?>
					</td>
					<td class="center"><?php echo $c1 ? '<span style="color:var(--success)">✓ ' . $c1 . '</span>' : '<span class="text-muted">—</span>'; ?></td>
					<td class="center"><?php echo $c2 ? '<span style="color:var(--success)">✓ ' . $c2 . '</span>' : '<span class="text-muted">—</span>'; ?></td>
					<td class="center"><?php echo $c3 ? '<span style="color:var(--success)">✓ ' . $c3 . '</span>' : '<span class="text-muted">—</span>'; ?></td>
					<td class="center" style="white-space:nowrap">
						<a href="admin.php?page=meal-templates&id=<?php echo (int) $t['id']; ?>" class="btn btn-outline btn-sm"><?php _e( 'Изменить', 'meal-menu' ); ?></a>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="del-tpl-form" style="display:inline;margin-left:4px" data-label="<?php echo esc_attr( $t['label'] ); ?>">
							<?php wp_nonce_field( 'meal_delete_template' ); ?>
							<input type="hidden" name="action" value="meal_delete_template">
							<input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
							<input type="hidden" name="school_type" value="<?php echo esc_attr( $type ); ?>">
							<button type="submit" class="btn btn-danger btn-sm" title="<?php esc_attr_e( 'Удалить шаблон', 'meal-menu' ); ?>">✕</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>

<script>
(function() {
	// ─── Удаление с подтверждением ──────────────────────────────
	document.querySelectorAll('.del-tpl-form').forEach(function(form) {
		var btn = form.querySelector('button');
		var timer = null;
		var armed = false;
		form.addEventListener('submit', function(e) {
			if (!armed) {
				e.preventDefault();
				armed = true;
				btn.textContent = 'Удалить?';
				btn.style.minWidth = '72px';
				clearTimeout(timer);
				timer = setTimeout(function() {
					armed = false;
					btn.textContent = '✕';
					btn.style.minWidth = '';
				}, 3000);
			}
		});
	});

	// ─── Дропзона (пакетная загрузка) ───────────────────────────
	var dz = document.getElementById('dropzone');
	var dzFile = document.getElementById('dropzone-file');
	var dzProgress = document.getElementById('dropzone-progress');
	var dzProgressBar = document.getElementById('dropzone-progress-bar');
	var dzStatus = document.getElementById('dropzone-status');
	var type = '<?php echo esc_js( $type ); ?>';
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';

	['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(ev) {
		dz.addEventListener(ev, function(e) { e.preventDefault(); e.stopPropagation(); });
	});
	['dragenter', 'dragover'].forEach(function(ev) {
		dz.addEventListener(ev, function() { dz.classList.add('dz-active'); });
	});
	['dragleave', 'drop'].forEach(function(ev) {
		dz.addEventListener(ev, function() { dz.classList.remove('dz-active'); });
	});

	dz.addEventListener('drop', function(e) {
		var files = Array.from(e.dataTransfer.files);
		if (files.length) uploadBatch(files);
	});

	dz.addEventListener('click', function() { dzFile.click(); });
	dzFile.addEventListener('change', function() {
		var files = Array.from(this.files);
		if (files.length) uploadBatch(files);
		this.value = '';
	});

	function uploadBatch(files) {
		var xlsxFiles = files.filter(function(f) { return f.name.match(/\.xlsx$/i); });
		if (xlsxFiles.length === 0) {
			dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Нет файлов .xlsx', 'meal-menu' ); ?></span>';
			return;
		}
		var total = xlsxFiles.length;
		var done = 0;
		var errors = [];
		dzProgress.style.display = '';

		function uploadNext(i) {
			if (i >= total) {
				dzProgressBar.style.width = '100%';
				var msg = '<?php _e( 'Загружено:', 'meal-menu' ); ?> ' + done + '/' + total;
				if (errors.length) msg += ' | <?php _e( 'Ошибок:', 'meal-menu' ); ?> ' + errors.length;
				dzStatus.innerHTML = msg;
				if (done > 0) {
					setTimeout(function() { location.reload(); }, 1200);
				}
				return;
			}

			var file = xlsxFiles[i];
			dzStatus.innerHTML = '<span><?php _e( 'Файл', 'meal-menu' ); ?> ' + (i + 1) + '/' + total + ': ' + escapeHtml(file.name) + '</span>';
			dzProgressBar.style.width = Math.round((i / total) * 100) + '%';

			var fd = new FormData();
			fd.append('action', 'meal_import_dropzone');
			fd.append('nonce', nonce);
			fd.append('type', type);
			fd.append('xlsx', file);

			var xhr = new XMLHttpRequest();
			xhr.onload = function() {
				if (xhr.status !== 200) {
					errors.push(file.name + ': server error');
				} else {
					try {
						var r = JSON.parse(xhr.responseText);
						if (r.ok) { done++; }
						else { errors.push(file.name + ': ' + (r.error || '?')); }
					} catch(e) {
						errors.push(file.name + ': parse error');
					}
				}
				uploadNext(i + 1);
			};
			xhr.onerror = function() {
				errors.push(file.name + ': network error');
				uploadNext(i + 1);
			};
			xhr.open('POST', ajaxUrl, true);
			xhr.send(fd);
		}

		uploadNext(0);
	}

	function escapeHtml(s) {
		var d = document.createElement('div');
		d.textContent = s;
		return d.innerHTML;
	}
})();
</script>
