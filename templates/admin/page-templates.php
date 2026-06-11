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

$meal_labels = array(
	'breakfast'       => __( 'Завтрак', 'meal-menu' ),
	'breakfast2'      => __( 'Завтрак 2', 'meal-menu' ),
	'lunch'           => __( 'Обед', 'meal-menu' ),
	'afternoon_snack' => __( 'Полдник', 'meal-menu' ),
	'dinner'          => __( 'Ужин', 'meal-menu' ),
	'dinner2'         => __( 'Ужин 2', 'meal-menu' ),
);
?>
<style>
.photo-modal-overlay {
	position: fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;
	display:flex;align-items:center;justify-content:center;
}
.photo-modal-dialog {
	background:#fff;border-radius:6px;max-width:98vw;width:1000px;
	max-height:95vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.2);
}
.photo-modal-header {
	display:flex;align-items:center;justify-content:space-between;
	padding:14px 20px;border-bottom:1px solid #dcdcde;font-weight:600;font-size:1rem;
}
.photo-modal-close {
	background:none;border:none;font-size:1.4rem;cursor:pointer;color:#646970;padding:0 4px;
}
.photo-modal-close:hover{color:#1d2327}
.photo-modal-body {
	flex:1;overflow-y:auto;padding:20px;min-height:200px;
}
.photo-modal-footer {
	padding:12px 20px;border-top:1px solid #dcdcde;
	display:flex;gap:12px;align-items:center;flex-wrap:wrap;
}
.photo-modal-loader {
	text-align:center;padding:40px;color:#646970;font-size:.95rem;
}
.photo-error { color:#d63638;font-size:.85rem;margin-bottom:12px; }
.photo-items-table { width:100%;border-collapse:collapse;font-size:.82rem; }
.photo-items-table th {
	background:#f6f7f7;padding:5px 6px;text-align:left;font-weight:600;
	border:1px solid #dcdcde;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;
}
.photo-items-table td { border:1px solid #dcdcde;padding:2px 4px; }
.photo-items-table input,.photo-items-table select {
	width:100%;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px;background:#fff;
}
.photo-items-table input:focus,.photo-items-table select:focus {
	outline:none;border-color:#2271b1;box-shadow:0 0 0 1px #2271b1;
}
.photo-items-table .del-row {
	cursor:pointer;color:#d63638;background:none;border:none;font-size:1rem;padding:2px 6px;
}
.photo-form-row { display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;align-items:end; }
.photo-form-row .field label { display:block;font-size:.78rem;font-weight:600;color:#1d2327;margin-bottom:4px; }
</style>

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
			<span class="dropzone-text"><?php _e( 'Перетащите XLSX-файлы или фото меню сюда', 'meal-menu' ); ?></span>
			<span class="dropzone-hint"><?php _e( '.xlsx → шаблон · Фото (JPEG/PNG) → AI-распознавание', 'meal-menu' ); ?></span>
			<input type="file" id="dropzone-file" multiple accept=".xlsx,image/jpeg,image/png,image/webp" style="display:none">
		</div>
		<div id="dropzone-progress" class="dropzone-progress" style="display:none">
			<div class="dropzone-progress-bar" id="dropzone-progress-bar"></div>
			<div id="dropzone-status"></div>
		</div>
	</div>

	<?php if ( ! \Meal_Menu\Importer_Photo::is_ai_available() ): ?>
	<div class="alert alert-error" style="margin-bottom:12px;font-size:.82rem">
		<?php _e( 'AI-распознавание фото недоступно: не настроен коннектор.', 'meal-menu' ); ?>
		<a href="<?php echo admin_url( 'options-connectors.php' ); ?>" style="text-decoration:underline"><?php _e( 'Настроить', 'meal-menu' ); ?></a>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $gaps ) ): ?>
	<div class="alert alert-error" style="margin-bottom:16px">
		<?php _e( 'В цикле пропущены шаблоны:', 'meal-menu' ); ?> <strong>№<?php echo implode( ', №', array_map( 'esc_html', $gaps ) ); ?></strong>.
		<?php _e( 'Дни с такими номерами не будут назначаться в календаре.', 'meal-menu' ); ?>
	</div>
	<?php endif; ?>

	<?php
	$active_meals = array();
	foreach ( $templates as $t ) {
		$items = $is_camp ? $db->get_camp_template_items( (int) $t['id'] ) : $db->get_template_items( (int) $t['id'] );
		foreach ( $meal_labels as $key => $label ) {
			if ( ! empty( $items[ $key ] ) ) {
				$active_meals[ $key ] = $label;
			}
		}
	}
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

<script id="photo-item-row-tpl" type="text/template">
	<tr>
		<td><select class="pm-meal" style="width:100%;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px">
			<option value="breakfast"><?php echo esc_html( $meal_labels['breakfast'] ); ?></option>
			<option value="breakfast2"><?php echo esc_html( $meal_labels['breakfast2'] ); ?></option>
			<option value="lunch"><?php echo esc_html( $meal_labels['lunch'] ); ?></option>
			<option value="afternoon_snack"><?php echo esc_html( $meal_labels['afternoon_snack'] ); ?></option>
			<option value="dinner"><?php echo esc_html( $meal_labels['dinner'] ); ?></option>
			<option value="dinner2"><?php echo esc_html( $meal_labels['dinner2'] ); ?></option>
		</select></td>
		<td><input type="text" class="pm-section" style="padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px;width:100%"></td>
		<td><input type="text" class="pm-dish" style="padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px;width:100%"></td>
		<td><input type="text" class="pm-recipe" style="width:70px;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px"></td>
		<td><input type="number" class="pm-grams" style="width:70px;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px" step="1" min="0"></td>
		<td><input type="number" class="pm-kcal" style="width:65px;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>
		<td><input type="number" class="pm-protein" style="width:60px;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>
		<td><input type="number" class="pm-fat" style="width:60px;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>
		<td><input type="number" class="pm-carbs" style="width:60px;padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>
		<td style="text-align:center"><button type="button" class="pm-del-row" style="cursor:pointer;color:#d63638;background:none;border:none;font-size:1rem;padding:2px 6px">&times;</button></td>
	</tr>
</script>

<script>
(function() {
	var type = '<?php echo esc_js( $type ); ?>';
	var isCamp = <?php echo $is_camp ? 'true' : 'false'; ?>;
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';
	var aiAvailable = <?php echo \Meal_Menu\Importer_Photo::is_ai_available() ? 'true' : 'false'; ?>;

	var mealLabels = <?php echo json_encode( $meal_labels ); ?>;

	function apiPost(payload, callback) {
		payload.nonce = nonce;
		if (isCamp) payload.camp = 1;
		jQuery.post(ajaxUrl + '?action=meal_bulk_delete_templates', JSON.stringify(payload), function(r) {
			try { callback(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { callback({ ok: false, error: 'Ошибка ответа' }); }
		}).fail(function() { callback({ ok: false, error: 'Сетевая ошибка' }); });
	}

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

		tbl.addEventListener('click', function(e) {
			var btn = e.target.closest('.del-single');
			if (!btn) return;
			if (!confirm('Удалить этот шаблон?')) return;
			apiPost({ action: 'delete', id: parseInt(btn.dataset.id) }, function(r) {
				if (r.ok) location.reload();
				else document.getElementById('bulk-status').innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || 'Ошибка') + '</span>';
			});
		});

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

	// ── Dropzone: XLSX + Images ──────────────────────────────

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
		var xlsxFiles = [];
		var imgFiles = [];
		files.forEach(function(f) {
			if (f.name.match(/\.xlsx$/i)) xlsxFiles.push(f);
			else if (f.type.match(/^image\/(jpeg|png|webp)$/)) imgFiles.push(f);
		});

		if (xlsxFiles.length === 0 && imgFiles.length === 0) {
			dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Поддерживаются .xlsx, JPEG, PNG, WebP', 'meal-menu' ); ?></span>';
			return;
		}

		if (xlsxFiles.length > 0) {
			uploadXlsxBatch(xlsxFiles);
	} else if (imgFiles.length > 0) {
		if (!aiAvailable) {
			dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'AI не настроен. Настройте коннектор в Settings → Connectors.', 'meal-menu' ); ?></span>';
			return;
		}
		uploadPhoto(imgFiles[0]);
	}
	}

	// ── XLSX import (legacy) ──────────────────────────────────

	function uploadXlsxBatch(files) {
		var total = files.length;
		var done = 0;
		var errors = [];
		dzProgress.style.display = '';

		function next(i) {
			if (i >= total) {
				dzProgressBar.style.width = '100%';
				var msg = '<?php _e( 'Загружено:', 'meal-menu' ); ?> ' + done + '/' + total;
				if (errors.length) msg += ' | <?php _e( 'Ошибок:', 'meal-menu' ); ?> ' + errors.length;
				dzStatus.innerHTML = msg;
				if (done > 0) setTimeout(function() { location.reload(); }, 1200);
				return;
			}
			var file = files[i];
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
				if (xhr.status !== 200) { errors.push(file.name + ': server error'); }
				else {
					try {
						var r = JSON.parse(xhr.responseText);
						if (r.ok) done++; else errors.push(file.name + ': ' + (r.error || '?'));
					} catch(e) { errors.push(file.name + ': parse error'); }
				}
				next(i + 1);
			};
			xhr.onerror = function() { errors.push(file.name + ': network error'); next(i + 1); };
			xhr.open('POST', ajaxUrl, true);
			xhr.send(fd);
		}
		next(0);
	}

	// ── Photo import (AI) ────────────────────────────────────

	var currentPhotoPath = null;
	var analysisInterval = null;
	var analysisStartTime = null;

	function startAnalysisBar() {
		dzProgress.style.display = '';
		dzProgressBar.style.width = '30%';
		dzProgressBar.style.transition = 'none';
		analysisStartTime = Date.now();
		dzStatus.textContent = '<?php _e( 'Распознавание через AI… 0 сек', 'meal-menu' ); ?>';
		var p = 30;
		analysisInterval = setInterval(function() {
			var elapsed = Math.round((Date.now() - analysisStartTime) / 1000);
			p += Math.random() * 3;
			if (p > 85) p = 85;
			dzProgressBar.style.transition = 'width .8s ease';
			dzProgressBar.style.width = p + '%';
			dzStatus.textContent = '<?php _e( 'Распознавание через AI…', 'meal-menu' ); ?> ' + elapsed + ' <?php _e( 'сек', 'meal-menu' ); ?>';
		}, 2000);
	}

	function stopAnalysisBar(success) {
		if (analysisInterval) { clearInterval(analysisInterval); analysisInterval = null; }
		if (success) {
			dzProgressBar.style.transition = 'width .3s ease';
			dzProgressBar.style.width = '100%';
			setTimeout(function() { dzProgress.style.display = 'none'; }, 600);
		} else {
			setTimeout(function() { dzProgress.style.display = 'none'; }, 100);
		}
	}

	function uploadPhoto(file) {
		dzProgress.style.display = '';
		dzProgressBar.style.transition = 'width .3s ease';
		dzProgressBar.style.width = '0%';
		dzStatus.textContent = '<?php _e( 'Загрузка фото… 0%', 'meal-menu' ); ?>';

		var fd = new FormData();
		fd.append('action', 'meal_photo_upload');
		fd.append('nonce', nonce);
		fd.append('photo', file);

		var xhr = new XMLHttpRequest();
		xhr.upload.onprogress = function(e) {
			if (e.lengthComputable) {
				var pct = Math.round((e.loaded / e.total) * 30);
				dzProgressBar.style.width = pct + '%';
				dzStatus.textContent = '<?php _e( 'Загрузка фото…', 'meal-menu' ); ?> ' + Math.round((e.loaded / e.total) * 100) + '%';
			}
		};
		xhr.onload = function() {
			if (xhr.status !== 200) { dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Ошибка загрузки', 'meal-menu' ); ?></span>'; return; }
			try {
				var r = JSON.parse(xhr.responseText);
				if (r.ok) {
					currentPhotoPath = r.path;
					startAnalysisBar();
					analyzePhoto(r.path);
				} else {
					dzStatus.innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || '<?php _e( 'Ошибка', 'meal-menu' ); ?>') + '</span>';
				}
			} catch(e) { dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Ошибка ответа', 'meal-menu' ); ?></span>'; }
		};
		xhr.onerror = function() { dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Сетевая ошибка', 'meal-menu' ); ?></span>'; };
		xhr.open('POST', ajaxUrl, true);
		xhr.send(fd);
	}

	function analyzePhoto(path) {
		var fd = new FormData();
		fd.append('action', 'meal_photo_analyze');
		fd.append('nonce', nonce);
		fd.append('path', path);

		var xhr = new XMLHttpRequest();
		xhr.timeout = 120000;
		xhr.ontimeout = function() {
			stopAnalysisBar(false);
			dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Таймаут AI-запроса. Попробуйте ещё раз.', 'meal-menu' ); ?></span>';
		};
		xhr.onload = function() {
			if (xhr.status !== 200) { stopAnalysisBar(false); dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Ошибка анализа', 'meal-menu' ); ?></span>'; return; }
			try {
				var r = JSON.parse(xhr.responseText);
				if (r.ok) {
					var cnt = (r.data && r.data.items) ? r.data.items.length : 0;
					stopAnalysisBar(true);
					dzStatus.innerHTML = '<span style="color:var(--success)"><?php _e( '✓ Распознано', 'meal-menu' ); ?> ' + cnt + ' <?php _e( 'блюд', 'meal-menu' ); ?></span>';
					setTimeout(function() { dzStatus.innerHTML = ''; }, 3000);
					showPhotoModal(r.data);
				} else {
					stopAnalysisBar(false);
					dzStatus.innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || '<?php _e( 'Ошибка распознавания', 'meal-menu' ); ?>') + '</span>';
				}
			} catch(e) { stopAnalysisBar(false); dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Ошибка ответа', 'meal-menu' ); ?></span>'; }
		};
		xhr.onerror = function() { stopAnalysisBar(false); dzStatus.innerHTML = '<span style="color:var(--error)"><?php _e( 'Сетевая ошибка', 'meal-menu' ); ?></span>'; };
		xhr.open('POST', ajaxUrl, true);
		xhr.send(fd);
	}

	function showPhotoModal(data) {
		var items = data.items || [];
		var existing = document.querySelector('.photo-modal-overlay');
		if (existing) existing.remove();

		var overlay = document.createElement('div');
		overlay.className = 'photo-modal-overlay';
		overlay.innerHTML =
			'<div class="photo-modal-dialog">' +
				'<div class="photo-modal-header">' +
					'<span><?php _e( 'Результат распознавания меню', 'meal-menu' ); ?></span>' +
					'<button class="photo-modal-close">&times;</button>' +
				'</div>' +
				'<div class="photo-modal-body">' +
					'<div class="photo-error" style="display:none"></div>' +
					'<div class="photo-form-row">' +
						'<div class="field"><label><?php _e( 'Тип отделения', 'meal-menu' ); ?></label>' +
							'<select id="pm-type" class="form-control" style="width:auto;min-width:160px"><?php
								foreach ( $type_labels as $code => $label ) {
									echo '<option value="' . esc_attr( $code ) . '"' . ( $code === $type ? ' selected' : '' ) . '>' . esc_html( $label ) . '</option>';
								}
							?></select></div>' +
						'<div class="field"><label><?php _e( 'День №', 'meal-menu' ); ?></label>' +
							'<input type="number" id="pm-day" class="form-control" style="width:80px" min="1" max="99" placeholder="<?php _e( 'авто', 'meal-menu' ); ?>"></div>' +
						'<div class="field" style="display:flex;align-items:end;padding-bottom:4px"><label class="opt-label" style="cursor:pointer;gap:6px;font-size:.88rem">' +
							'<input type="checkbox" id="pm-boarding"> <?php _e( 'Интернат', 'meal-menu' ); ?></label></div>' +
					'</div>' +
					'<div id="pm-items-wrap" style="overflow-x:auto"></div>' +
				'</div>' +
				'<div class="photo-modal-footer">' +
					'<button type="button" id="pm-save" class="btn btn-primary"><?php _e( '💾 Сохранить шаблон', 'meal-menu' ); ?></button>' +
					'<button type="button" id="pm-retry" class="btn btn-outline btn-sm"><?php _e( '🔄 Анализировать заново', 'meal-menu' ); ?></button>' +
					'<button type="button" id="pm-cancel" class="btn btn-outline btn-sm"><?php _e( 'Отмена', 'meal-menu' ); ?></button>' +
					'<span id="pm-status" class="btn-msg"></span>' +
				'</div>' +
			'</div>';
		document.body.appendChild(overlay);

		if (data.day_number) document.getElementById('pm-day').value = data.day_number;
		renderItems(items);

		overlay.querySelector('.photo-modal-close').addEventListener('click', function() { overlay.remove(); });
		document.getElementById('pm-cancel').addEventListener('click', function() { overlay.remove(); });

		document.getElementById('pm-retry').addEventListener('click', function() {
			overlay.remove();
			if (currentPhotoPath) {
				dzStatus.textContent = '<?php _e( 'Повторный анализ…', 'meal-menu' ); ?>';
				dzProgress.style.display = '';
				analyzePhoto(currentPhotoPath);
			}
		});

		document.getElementById('pm-save').addEventListener('click', function() { saveFromModal(overlay); });
	}

	function renderItems(items) {
		var wrap = document.getElementById('pm-items-wrap');
		if (!items.length) {
			wrap.innerHTML = '<div class="alert alert-error"><?php _e( 'Нет блюд для отображения', 'meal-menu' ); ?></div>';
			return;
		}

		var html = '<table class="photo-items-table"><thead><tr>' +
			'<th style="width:100px"><?php _e( 'Приём пищи', 'meal-menu' ); ?></th>' +
			'<th style="width:90px"><?php _e( 'Категория', 'meal-menu' ); ?></th>' +
			'<th><?php _e( 'Блюдо', 'meal-menu' ); ?></th>' +
			'<th style="width:75px"><?php _e( 'ТТК №', 'meal-menu' ); ?></th>' +
			'<th style="width:70px"><?php _e( 'Вес, г', 'meal-menu' ); ?></th>' +
			'<th style="width:65px"><?php _e( 'Ккал', 'meal-menu' ); ?></th>' +
			'<th style="width:60px"><?php _e( 'Белки', 'meal-menu' ); ?></th>' +
			'<th style="width:60px"><?php _e( 'Жиры', 'meal-menu' ); ?></th>' +
			'<th style="width:60px"><?php _e( 'Углев.', 'meal-menu' ); ?></th>' +
			'<th style="width:30px"></th>' +
		'</tr></thead><tbody>';

		items.forEach(function(item) {
			html += '<tr>';
			html += '<td><select class="pm-meal" style="padding:3px 5px;font-size:.82rem;border:1px solid #c3c4c7;border-radius:3px;width:100%">';
			['breakfast','breakfast2','lunch','afternoon_snack','dinner','dinner2'].forEach(function(m) {
				html += '<option value="' + m + '"' + (item.meal_type === m ? ' selected' : '') + '>' + escapeHtml(mealLabels[m] || m) + '</option>';
			});
			html += '</select></td>';
			html += '<td><input type="text" class="pm-section" value="' + escapeHtml(item.section || '') + '" style="padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px;width:100%"></td>';
			html += '<td><input type="text" class="pm-dish" value="' + escapeHtml(item.dish_name || '') + '" style="padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px;width:100%"></td>';
			html += '<td><input type="text" class="pm-recipe" value="' + escapeHtml(item.recipe_num || '') + '" style="width:100%;padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px"></td>';
			html += '<td><input type="number" class="pm-grams" value="' + (item.grams != null ? item.grams : '') + '" style="width:100%;padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px" step="1" min="0"></td>';
			html += '<td><input type="number" class="pm-kcal" value="' + (item.kcal != null ? item.kcal : '') + '" style="width:100%;padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>';
			html += '<td><input type="number" class="pm-protein" value="' + (item.protein != null ? item.protein : '') + '" style="width:100%;padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>';
			html += '<td><input type="number" class="pm-fat" value="' + (item.fat != null ? item.fat : '') + '" style="width:100%;padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>';
			html += '<td><input type="number" class="pm-carbs" value="' + (item.carbs != null ? item.carbs : '') + '" style="width:100%;padding:3px 5px;border:1px solid #c3c4c7;border-radius:3px" step="0.1" min="0"></td>';
			html += '<td style="text-align:center"><button type="button" class="pm-del-row" style="cursor:pointer;color:#d63638;background:none;border:none;font-size:1rem;padding:2px 6px">&times;</button></td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		html += '<div style="margin-top:8px"><button type="button" id="pm-add-row" class="btn btn-outline btn-sm">+ <?php _e( 'Добавить строку', 'meal-menu' ); ?></button></div>';
		wrap.innerHTML = html;

		wrap.querySelectorAll('.pm-del-row').forEach(function(btn) {
			btn.addEventListener('click', function() { var tr = this.closest('tr'); if (tr) tr.remove(); });
		});
		document.getElementById('pm-add-row').addEventListener('click', function() {
			var tpl = document.getElementById('photo-item-row-tpl');
			if (!tpl) return;
			var tbody = wrap.querySelector('tbody');
			if (tbody) {
				var tr = document.createElement('tr');
				tr.innerHTML = tpl.innerHTML;
				tbody.appendChild(tr);
				tr.querySelector('.pm-del-row').addEventListener('click', function() { tr.remove(); });
			}
		});
	}

	function saveFromModal(overlay) {
		var rows = [];
		overlay.querySelectorAll('#pm-items-wrap tbody tr').forEach(function(tr) {
			var dish = tr.querySelector('.pm-dish');
			if (!dish || !dish.value.trim()) return;
			rows.push({
				meal_type:  (tr.querySelector('.pm-meal') || {}).value || 'breakfast',
				section:    (tr.querySelector('.pm-section') || {}).value || '',
				dish_name:  dish.value.trim(),
				recipe_num: (tr.querySelector('.pm-recipe') || {}).value || '',
				grams:      parseNum(tr.querySelector('.pm-grams')),
				kcal:       parseNum(tr.querySelector('.pm-kcal')),
				protein:    parseNum(tr.querySelector('.pm-protein')),
				fat:        parseNum(tr.querySelector('.pm-fat')),
				carbs:      parseNum(tr.querySelector('.pm-carbs')),
			});
		});

		if (!rows.length) {
			showModalError(overlay, '<?php _e( 'Добавьте хотя бы одно блюдо', 'meal-menu' ); ?>');
			return;
		}

		var btn = document.getElementById('pm-save');
		var status = document.getElementById('pm-status');
		btn.disabled = true;
		status.innerHTML = '<span><?php _e( 'Сохранение…', 'meal-menu' ); ?></span>';

		jQuery.post(ajaxUrl, {
			action: 'meal_photo_save_template',
			nonce: nonce,
			type: document.getElementById('pm-type').value,
			day_number: document.getElementById('pm-day').value || '',
			is_boarding: document.getElementById('pm-boarding').checked ? 1 : 0,
			items_json: JSON.stringify(rows),
		}, function(r) {
			if (r.ok) {
				status.innerHTML = '<span style="color:var(--success)"><?php _e( '✓ Шаблон сохранён, перезагрузка…', 'meal-menu' ); ?></span>';
				btn.disabled = true;
				setTimeout(function() { overlay.remove(); location.reload(); }, 1000);
			} else {
				status.innerHTML = '<span style="color:var(--error)">' + escapeHtml(r.error || '<?php _e( 'Ошибка сохранения', 'meal-menu' ); ?>') + '</span>';
				btn.disabled = false;
			}
		}, 'json').fail(function(jqXHR, textStatus) {
			status.innerHTML = '<span style="color:var(--error)"><?php _e( 'Сетевая ошибка:', 'meal-menu' ); ?> ' + escapeHtml(textStatus) + '</span>';
			btn.disabled = false;
		});
	}

	function showModalError(overlay, msg) {
		var el = overlay.querySelector('.photo-error');
		if (el) { el.textContent = msg; el.style.display = ''; }
	}

	function parseNum(el) {
		if (!el || el.value === '') return null;
		var v = parseFloat(el.value);
		return isNaN(v) ? null : v;
	}

	function escapeHtml(s) {
		var d = document.createElement('div');
		d.textContent = s;
		return d.innerHTML;
	}
})();
</script>
