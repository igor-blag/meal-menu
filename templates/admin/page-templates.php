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

$is_camp = ! empty( $_GET['camp'] ) && $type === 'sm' && ! empty( $db->get_department( 'sm' )['has_summer_camp'] );

$templates = $is_camp ? $db->get_camp_templates( $type ) : $db->get_templates( $type );
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
		<h1 class="page-title" style="border:none;margin:0"><?php echo esc_html( $cycle_title ) . ( $is_camp ? ' · Летний лагерь' : '' ); ?></h1>
		<div class="flex gap-2">
			<?php if ( $type === 'sm' && $has_tm && ! $is_camp ): ?>
			<a href="<?php echo esc_url( $upload_dir['baseurl'] . '/meal-menu/tm' . current_time( 'Y' ) . '-sm.xlsx' ); ?>" class="btn btn-outline btn-sm" download>&#x2193; tm-файл</a>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
				<?php wp_nonce_field( 'meal_add_template' ); ?>
				<input type="hidden" name="action" value="meal_add_template">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
				<?php if ( $is_camp ): ?>
				<input type="hidden" name="camp" value="1">
				<?php endif; ?>
				<button type="submit" class="btn btn-primary btn-sm">+ <?php _e( 'Добавить день', 'meal-menu' ); ?></button>
			</form>
		</div>
	</div>

	<div class="tab-bar">
		<?php foreach ( $type_labels as $t => $label ): ?>
		<a href="admin.php?page=meal-templates&type=<?php echo esc_attr( $t ); ?>"
		   class="tab-item<?php echo ! $is_camp && $type === $t ? ' active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
		<?php if ( $db->get_department( 'sm' )['has_summer_camp'] ?? false ): ?>
		<a href="admin.php?page=meal-templates&type=sm&camp=1"
		   class="tab-item<?php echo $is_camp ? ' active' : ''; ?>"><?php _e( 'Летний лагерь', 'meal-menu' ); ?></a>
		<?php endif; ?>
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

	<div id="tm-import" class="panel" style="margin-bottom:16px">
		<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
			<span style="font-weight:500;font-size:.88rem"><?php _e( 'Импорт из типового меню (TM-файл)', 'meal-menu' ); ?></span>
			<span class="text-muted" style="font-size:.78rem"><?php _e( 'Заменяет все шаблоны текущего цикла данными из TM-файла', 'meal-menu' ); ?></span>
		</div>
		<div style="display:flex;align-items:center;gap:12px;margin-top:8px">
			<input type="file" id="tm-file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
			<button type="button" class="btn btn-primary btn-sm" id="btn-import-tm"><?php _e( 'Импортировать', 'meal-menu' ); ?></button>
			<span id="tm-status" class="text-muted" style="font-size:.78rem"></span>
		</div>
	</div>

	<?php if ( ! empty( $gaps ) ): ?>
	<div class="alert alert-error" style="margin-bottom:16px">
		<?php _e( 'В цикле пропущены шаблоны:', 'meal-menu' ); ?> <strong>№<?php echo implode( ', №', array_map( 'esc_html', $gaps ) ); ?></strong>.
		<?php _e( 'Дни с такими номерами не будут назначаться в календаре.', 'meal-menu' ); ?>
	</div>
	<?php endif; ?>

	<?php
	$meal_labels = array(
		'breakfast'       => __( 'Завтрак', 'meal-menu' ),
		'breakfast2'      => __( 'Завтрак 2', 'meal-menu' ),
		'lunch'           => __( 'Обед', 'meal-menu' ),
		'afternoon_snack' => __( 'Полдник', 'meal-menu' ),
		'dinner'          => __( 'Ужин', 'meal-menu' ),
		'dinner2'         => __( 'Ужин 2', 'meal-menu' ),
	);

	$active_meals = array();
	foreach ( $templates as $t ) {
		$items = $is_camp ? $db->get_camp_template_items( (int) $t['id'] ) : $db->get_template_items( (int) $t['id'] );
		foreach ( $meal_labels as $key => $label ) {
			if ( ! empty( $items[ $key ] ) ) {
				$active_meals[ $key ] = $label;
			}
		}
	}
	// If no items yet, show at least breakfast and lunch so table isn't blank
	if ( empty( $active_meals ) ) {
		$active_meals = array(
			'breakfast' => $meal_labels['breakfast'],
			'lunch'     => $meal_labels['lunch'],
		);
	}
	?>

	<?php if ( $cycle_len === 0 ): ?>
	<div class="alert alert-error"><?php _e( 'Шаблоны не добавлены. Нажмите «+ Добавить день» чтобы начать.', 'meal-menu' ); ?></div>
	<?php else: ?>
	<div class="panel">
		<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;min-height:28px">
			<button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected" disabled><?php _e( 'Удалить выбранные', 'meal-menu' ); ?></button>
			<span id="bulk-status" class="text-muted" style="font-size:.78rem"></span>
		</div>
		<table class="menu-table">
			<thead>
				<tr>
					<th style="width:32px"><input type="checkbox" id="select-all"></th>
					<th style="width:50px">№</th>
					<th><?php _e( 'Название', 'meal-menu' ); ?></th>
					<?php foreach ( $active_meals as $key => $label ): ?>
					<th style="width:110px"><?php echo esc_html( $label ); ?></th>
					<?php endforeach; ?>
					<th style="width:160px"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $t ):
					$items = $is_camp ? $db->get_camp_template_items( (int) $t['id'] ) : $db->get_template_items( (int) $t['id'] );
				?>
				<tr>
					<td class="center"><input type="checkbox" class="tpl-select" value="<?php echo (int) $t['id']; ?>"></td>
					<td class="center"><?php echo (int) $t['day_number']; ?></td>
					<td>
						<?php echo esc_html( $t['label'] ); ?>
						<?php if ( $t['is_boarding'] ): ?>
							<span style="font-size:.72em;background:#e8f0fe;color:#1a56db;padding:1px 5px;border-radius:3px;margin-left:6px;vertical-align:middle">ИНТЕРНАТ</span>
						<?php endif; ?>
					</td>
					<?php foreach ( $active_meals as $key => $label ):
						$cnt = count( $items[ $key ] ?? array() );
					?>
					<td class="center"><?php echo $cnt ? '<span style="color:var(--success)">✓ ' . $cnt . '</span>' : '<span class="text-muted">—</span>'; ?></td>
					<?php endforeach; ?>
					<td class="center" style="white-space:nowrap">
						<a href="admin.php?page=meal-templates&id=<?php echo (int) $t['id']; ?><?php echo $is_camp ? '&camp=1' : ''; ?>" class="btn btn-outline btn-sm"><?php _e( 'Изменить', 'meal-menu' ); ?></a>
						<button type="button" class="btn btn-danger btn-sm del-single" data-id="<?php echo (int) $t['id']; ?>" title="<?php esc_attr_e( 'Удалить шаблон', 'meal-menu' ); ?>">✕</button>
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
	var type = '<?php echo esc_js( $type ); ?>';
	var isCamp = <?php echo $is_camp ? 'true' : 'false'; ?>;
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';

	function apiPost(payload, callback) {
		payload.nonce = nonce;
		if (isCamp) payload.camp = 1;
		jQuery.post(ajaxUrl + '?action=meal_bulk_delete_templates', JSON.stringify(payload), function(r) {
			try { callback(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { callback({ ok: false, error: 'Ошибка ответа' }); }
		}).fail(function() { callback({ ok: false, error: 'Сетевая ошибка' }); });
	}

	// ── Select all (only if table exists) ──
	var tbl = document.querySelector('.menu-table');
	if (tbl) {
		document.getElementById('select-all').addEventListener('change', function() {
			document.querySelectorAll('.tpl-select').forEach(function(cb) { cb.checked = this.checked; }, this);
			toggleBulkBtn();
		});
		tbl.addEventListener('change', function(e) {
			if (e.target.classList.contains('tpl-select')) toggleBulkBtn();
		});
		function toggleBulkBtn() {
			var checked = document.querySelectorAll('.tpl-select:checked').length;
			document.getElementById('btn-delete-selected').disabled = checked === 0;
		}

		// ── Single delete ──
		tbl.addEventListener('click', function(e) {
			var btn = e.target.closest('.del-single');
			if (!btn) return;
			if (!confirm('Удалить этот шаблон?')) return;
			apiPost({ action: 'delete', id: parseInt(btn.dataset.id) }, function(r) {
				if (r.ok) location.reload();
				else document.getElementById('bulk-status').innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || 'Ошибка') + '</span>';
			});
		});

		// ── Bulk delete ──
		document.getElementById('btn-delete-selected').addEventListener('click', function() {
			var ids = [];
			document.querySelectorAll('.tpl-select:checked').forEach(function(cb) { ids.push(parseInt(cb.value)); });
			if (ids.length === 0) return;
			if (!confirm('Удалить ' + ids.length + ' шаблон(ов)?')) return;
			apiPost({ action: 'bulk_delete', ids: ids }, function(r) {
				if (r.ok) location.reload();
				else document.getElementById('bulk-status').innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || 'Ошибка') + '</span>';
			});
		});
	}

	var dz = document.getElementById('dropzone');
	var dzFile = document.getElementById('dropzone-file');
	var dzProgress = document.getElementById('dropzone-progress');
	var dzProgressBar = document.getElementById('dropzone-progress-bar');
	var dzStatus = document.getElementById('dropzone-status');

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
			if (isCamp) fd.append('camp', '1');
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

	// ── TM import ──
	var tmFile = document.getElementById('tm-file');
	var btnTm = document.getElementById('btn-import-tm');
	var tmStatus = document.getElementById('tm-status');
	btnTm.addEventListener('click', function() {
		var file = tmFile.files[0];
		if (!file) { tmStatus.innerHTML = '<span style="color:var(--error)">Выберите TM-файл</span>'; return; }
		if (!file.name.match(/\.xlsx$/i)) { tmStatus.innerHTML = '<span style="color:var(--error)">Нужен файл .xlsx</span>'; return; }
		tmStatus.innerHTML = 'Загрузка…';
		btnTm.disabled = true;
		var fd = new FormData();
		fd.append('action', 'meal_import_tm');
		fd.append('nonce', nonce);
		fd.append('type', type);
		if (isCamp) fd.append('camp', '1');
		fd.append('tm_xlsx', file);
		var xhr = new XMLHttpRequest();
		xhr.onload = function() {
			btnTm.disabled = false;
			if (xhr.status !== 200) {
				tmStatus.innerHTML = '<span style="color:var(--error)">Ошибка сервера</span>';
				return;
			}
			try {
				var r = JSON.parse(xhr.responseText);
				if (r.ok) {
					tmStatus.innerHTML = '<span style="color:var(--success)">Импортировано ' + r.imported + ' шаблон(ов)</span>';
					setTimeout(function() { location.reload(); }, 1200);
				} else {
					tmStatus.innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || 'Ошибка') + '</span>';
				}
			} catch(e) {
				tmStatus.innerHTML = '<span style="color:var(--error)">Ошибка ответа</span>';
			}
		};
		xhr.onerror = function() {
			btnTm.disabled = false;
			tmStatus.innerHTML = '<span style="color:var(--error)">Сетевая ошибка</span>';
		};
		xhr.open('POST', ajaxUrl, true);
		xhr.send(fd);
	});
})();
</script>
