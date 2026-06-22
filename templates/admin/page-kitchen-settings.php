<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$settings     = $db->get_kitchen_settings();
$departments  = $db->get_all_departments();
$ay_settings  = $db->get_academic_year_settings();
$inst_type    = $db->get_institution_type();

$merged_into = array();
$merged_by   = array();
foreach ( $departments as $d ) {
	if ( ! empty( $d['merged_with'] ) ) {
		$merged_into[ $d['merged_with'] ] = true;
		$merged_by[ $d['merged_with'] ][] = $d['dept_name'] ?: $d['label'];
	}
}
?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Настройки пищеблока', 'meal-menu' ); ?></h1>

	<div id="meal-msg"></div>

	<!-- Организация -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Образовательная организация', 'meal-menu' ); ?></div>
		<div class="form-group">
			<label for="org-name"><?php _e( 'Название', 'meal-menu' ); ?></label>
			<input type="text" id="org-name" class="form-control"
				value="<?php echo esc_attr( $settings['org_name'] ); ?>"
				placeholder="<?php esc_attr_e( 'Например: ГБОУ Школа №1234', 'meal-menu' ); ?>">
		</div>
		<div class="form-group">
			<label for="inst-type"><?php _e( 'Тип учреждения', 'meal-menu' ); ?></label>
			<select id="inst-type" class="form-control" style="max-width:300px">
				<option value="school" <?php selected( $inst_type, 'school' ); ?>><?php _e( 'Школа', 'meal-menu' ); ?></option>
				<option value="kindergarten" <?php selected( $inst_type, 'kindergarten' ); ?>><?php _e( 'Детский сад', 'meal-menu' ); ?></option>
			</select>
			<p class="text-muted" style="margin-top:4px;font-size:.78rem">
				<?php _e( 'В школе — публикация Excel, каникулы, утверждающее лицо. В детском саду — только отображение меню, без каникул и файлов.', 'meal-menu' ); ?>
			</p>
		</div>
	</div>

	<!-- Праздники -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Праздники', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2"><?php _e( 'Праздники задаются без года и применяются ежегодно. Если дата выпадает на субботу или воскресенье, выходной переносится на понедельник.', 'meal-menu' ); ?></p>

		<table class="vac-table" style="max-width:550px">
			<thead>
				<tr><th style="width:40%"><?php _e( 'Название', 'meal-menu' ); ?></th><th style="width:40%" colspan="2"><?php _e( 'Дата (ДД.ММ)', 'meal-menu' ); ?></th><th style="width:20%"></th></tr>
			</thead>
			<tbody id="holiday-body-admin"></tbody>
		</table>

		<div class="vac-actions" style="margin-top:8px">
			<button type="button" class="btn btn-outline btn-sm" id="btn-add-holiday-admin">+ <?php _e( 'Добавить праздник', 'meal-menu' ); ?></button>
			<button type="button" class="btn btn-outline btn-sm" id="btn-fill-holidays"><?php _e( 'Добавить государственные праздники РФ', 'meal-menu' ); ?></button>
		</div>
	</div>

	<!-- Отделения -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Отделения', 'meal-menu' ); ?></div>

		<?php if ( empty( $departments ) ): ?>
		<div id="dept-empty" style="padding:24px 0;text-align:center;color:var(--wp-muted)">
			<p style="font-size:.95rem;margin-bottom:8px"><?php _e( 'Сначала выберите тип учреждения выше.', 'meal-menu' ); ?></p>
			<p style="font-size:.82rem"><?php _e( 'Отделения будут созданы автоматически.', 'meal-menu' ); ?></p>
		</div>
		<?php else: ?>
		<p class="text-muted mb-2"><?php _e( 'Отметьте отделения вашей организации и настройте параметры каждого.', 'meal-menu' ); ?></p>
		<div id="dept-list">
		<?php foreach ( $departments as $d ):
			$wd = explode( ',', $d['workdays'] );
		?>
			<div class="dept-card<?php echo $d['is_enabled'] ? ' enabled' : ''; ?>" data-id="<?php echo (int) $d['id']; ?>" data-code="<?php echo esc_attr( $d['code'] ); ?>">
				<div class="dept-header">
					<input type="checkbox" class="dept-toggle"<?php echo $d['is_enabled'] ? ' checked' : ''; ?>>
					<span class="dept-title"><?php echo esc_html( $d['label'] ); ?></span>
					<?php if ( $d['note'] ): ?>
						<span class="dept-note"><?php echo esc_html( $d['note'] ); ?></span>
					<?php endif; ?>
				</div>
				<div class="dept-body">
					<div class="form-row">
						<div class="field" style="max-width:350px">
							<label><?php _e( 'Название', 'meal-menu' ); ?></label>
							<input type="text" class="inp-dept-name" value="<?php echo esc_attr( $d['dept_name'] ); ?>" placeholder="<?php echo esc_attr( $d['code'] === 'main' ? 'Старшеклассники' : $d['label'] ); ?>" data-code="<?php echo esc_attr( $d['code'] ); ?>">
						</div>
						<?php if ( ! $d['is_builtin'] ): ?>
						<div class="field" style="max-width:350px">
							<label><?php _e( 'Описание', 'meal-menu' ); ?></label>
							<input type="text" class="inp-dept-note" value="<?php echo esc_attr( $d['note'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Краткое описание отделения', 'meal-menu' ); ?>">
						</div>
						<div class="field" style="max-width:100px;display:flex;align-items:end">
							<button type="button" class="btn btn-outline btn-sm btn-delete-dept" data-id="<?php echo (int) $d['id']; ?>"><?php _e( 'Удалить', 'meal-menu' ); ?></button>
						</div>
						<?php endif; ?>
					</div>

					<div class="form-row">
						<div class="field">
							<label><?php _e( 'Рабочие дни', 'meal-menu' ); ?></label>
							<div class="wd-group">
								<?php foreach ( array( 1 => 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) as $i => $name ): ?>
								<label class="wd-label">
									<input type="checkbox" class="wd-chk" value="<?php echo $i; ?>"
										<?php echo in_array( (string) $i, $wd, true ) ? 'checked' : ''; ?>
										<?php echo $d['merged_with'] ? 'disabled' : ''; ?>>
									<?php echo $name; ?>
								</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>


				
					<div class="opt-row">
						<label class="opt-label school-only-field<?php echo $d['code'] === 'preschool' ? ' disabled' : ''; ?>">
							<input type="checkbox" class="chk-boarding"
								<?php echo $d['is_boarding'] ? 'checked' : ''; ?>
								<?php echo $d['code'] === 'preschool' ? 'disabled checked' : ''; ?>>
							<?php _e( 'Интернат (доп. приёмы пищи)', 'meal-menu' ); ?>
						</label>
						<label class="opt-label school-only-field">
							<input type="checkbox" class="chk-publish"
								<?php echo $d['publish_xlsx'] ? 'checked' : ''; ?>>
							<?php _e( 'Публикация файлов меню', 'meal-menu' ); ?>
						</label>
						<label class="opt-label school-only-field">
							<input type="checkbox" class="chk-ignore-vac"
								<?php echo ! empty( $d['ignore_vacations'] ) ? 'checked' : ''; ?>
								<?php echo $d['merged_with'] ? 'disabled' : ''; ?>>
							<?php _e( 'Без каникул (круглый год)', 'meal-menu' ); ?>
						</label>
					</div>

					<div class="form-row suffix-row school-only-field"<?php echo ! $d['publish_xlsx'] ? ' style="display:none"' : ''; ?>>
						<div class="field" style="max-width:300px">
							<label><?php _e( 'Постфикс файлов', 'meal-menu' ); ?></label>
							<?php if ( $d['is_builtin'] ): ?>
								<span class="suffix-display"><?php echo $d['file_suffix'] ?: __( '(без постфикса)', 'meal-menu' ); ?> &rarr; ГГГГ-ММ-ДД<?php echo esc_html( $d['file_suffix'] ); ?>.xlsx</span>
							<?php else: ?>
								<input type="text" class="inp-suffix" value="<?php echo esc_attr( $d['file_suffix'] ); ?>" placeholder="-custom">
							<?php endif; ?>
						</div>
					</div>

					<div class="form-row merge-select-row school-only-field"<?php echo isset( $merged_into[ $d['code'] ] ) ? ' style="display:none"' : ''; ?>>
					<div class="field" style="max-width:300px">
						<label><?php _e( 'Объединить календарь с', 'meal-menu' ); ?></label>
						<select class="sel-merge">
							<option value="">—</option>
							<?php foreach ( $departments as $other ): if ( $other['id'] === $d['id'] || ! $other['is_enabled'] ) continue; ?>
							<option value="<?php echo esc_attr( $other['code'] ); ?>"<?php echo $d['merged_with'] === $other['code'] ? ' selected' : ''; ?>><?php echo esc_html( $other['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="text-muted" style="font-size:.72rem;display:block;margin-top:4px"><?php _e( 'Календарь этого отделения будет использовать данные и генерировать файлы вместе с выбранным.', 'meal-menu' ); ?></span>
					</div>
				</div>
				<div class="form-row merge-static-row school-only-field"<?php echo isset( $merged_into[ $d['code'] ] ) ? '' : ' style="display:none"'; ?>>
					<div class="field" style="max-width:300px">
						<label><?php _e( 'Объединение календарей', 'meal-menu' ); ?></label>
						<span class="text-muted merge-static-text" style="font-size:.82rem;display:block;margin-top:4px"><?php printf( __( 'Источник данных для: %s', 'meal-menu' ), isset( $merged_by[ $d['code'] ] ) ? implode( ', ', $merged_by[ $d['code'] ] ) : '' ); ?></span>
					</div>
				</div>

				<?php if ( $d['code'] === 'sm' ): ?>
				<div class="form-row" style="margin-top:8px;padding-top:8px;border-top:1px solid var(--wp-border-subtle)">
					<div class="field" style="max-width:100%">
						<label class="opt-label">
							<input type="checkbox" class="chk-camp" <?php echo ! empty( $d['has_summer_camp'] ) ? 'checked' : ''; ?>>
							<?php _e( 'Летний лагерь', 'meal-menu' ); ?>
						</label>
						<div class="camp-fields"<?php echo empty( $d['has_summer_camp'] ) ? ' style="display:none"' : ''; ?>>
							<div class="form-row" style="margin-top:8px">
								<div class="field" style="max-width:200px">
									<label><?php _e( 'Начало лагеря', 'meal-menu' ); ?></label>
									<input type="date" class="camp-start" value="<?php echo esc_attr( $d['camp_start_date'] ?? '' ); ?>">
								</div>
								<div class="field" style="max-width:200px">
									<label><?php _e( 'Конец лагеря', 'meal-menu' ); ?></label>
									<input type="date" class="camp-end" value="<?php echo esc_attr( $d['camp_end_date'] ?? '' ); ?>">
								</div>
							</div>
							<div class="form-row" style="margin-top:8px">
								<div class="field">
									<label><?php _e( 'Рабочие дни лагеря', 'meal-menu' ); ?></label>
									<div class="wd-group">
										<?php $camp_wd = explode( ',', $d['camp_workdays'] ?? '1,2,3,4,5' ); ?>
										<?php foreach ( array( 1 => 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) as $i => $name ): ?>
										<label class="wd-label">
											<input type="checkbox" class="camp-wd-chk" value="<?php echo $i; ?>"
												<?php echo in_array( (string) $i, $camp_wd, true ) ? 'checked' : ''; ?>>
											<?php echo $name; ?>
										</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
							<div class="form-row" style="margin-top:8px">
								<div class="field" style="display:flex;gap:16px;flex-wrap:wrap">
									<label class="opt-label">
										<input type="checkbox" class="camp-boarding"
											<?php echo ! empty( $d['camp_is_boarding'] ) ? 'checked' : ''; ?>>
										<?php _e( 'Интернат', 'meal-menu' ); ?>
									</label>
									<label class="opt-label">
										<input type="checkbox" class="camp-publish"
											<?php echo ! empty( $d['camp_publish_xlsx'] ) ? 'checked' : ''; ?>>
										<?php _e( 'Публикация файлов', 'meal-menu' ); ?>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
		</div>

		<div class="mt-2">
			<button type="button" class="btn btn-outline btn-sm" id="btn-add-dept">+ <?php _e( 'Добавить отделение', 'meal-menu' ); ?></button>
		</div>
		<?php endif; ?>
		<div class="custom-dept-form" id="add-dept-form">
			<div class="form-row" style="display:flex;flex-wrap:nowrap;gap:12px">
				<div class="field" style="flex:2;min-width:0">
					<label><?php _e( 'Название', 'meal-menu' ); ?></label>
					<input type="text" id="new-dept-label" class="form-control" placeholder="<?php esc_attr_e( 'Ясельная группа', 'meal-menu' ); ?>">
				</div>
				<div class="field" style="flex:1;min-width:0">
					<label><?php _e( 'Код (латиница)', 'meal-menu' ); ?></label>
					<input type="text" id="new-dept-code" class="form-control" placeholder="nursery" pattern="[a-z0-9_]+">
				</div>
				<div class="field school-only-field" style="flex:1;min-width:0">
					<label><?php _e( 'Постфикс', 'meal-menu' ); ?></label>
					<input type="text" id="new-dept-suffix" class="form-control" placeholder="-nursery">
				</div>
			</div>
			<div class="mt-1">
				<button type="button" class="btn btn-primary btn-sm" id="btn-confirm-add"><?php _e( 'Создать', 'meal-menu' ); ?></button>
				<button type="button" class="btn btn-outline btn-sm" id="btn-cancel-add" style="margin-left:8px"><?php _e( 'Отмена', 'meal-menu' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Учебный год и каникулы -->
	<div class="panel school-only-section">
		<div class="panel-title"><?php _e( 'Учебный год и каникулы', 'meal-menu' ); ?></div>

		<div class="ay-fields">
			<div class="field">
				<label><?php _e( 'Начало уч. года (ДД.ММ)', 'meal-menu' ); ?></label>
				<input type="text" id="ay-start" placeholder="01.09">
			</div>
			<div class="field">
				<label><?php _e( 'Конец уч. года (ДД.ММ)', 'meal-menu' ); ?></label>
				<input type="text" id="ay-end" placeholder="31.05">
			</div>
			<div class="field" style="min-width:auto">
				<label class="opt-label" style="margin-top:20px">
					<input type="checkbox" id="ay-reset" <?php echo $ay_settings['reset_cycle_after_vacation'] ? 'checked' : ''; ?>>
					<?php _e( 'Сбрасывать цикл после каникул', 'meal-menu' ); ?>
				</label>
			</div>
		</div>

		<hr>

		<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
			<label style="font-size:.88rem;"><?php _e( 'Учебный год:', 'meal-menu' ); ?></label>
			<select class="vac-year-select" id="vac-year"></select>
		</div>

		<h2 style="font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;color:var(--wp-muted);margin:16px 0 8px"><?php _e( 'Каникулы', 'meal-menu' ); ?></h2>
		<table class="vac-table">
			<thead>
				<tr><th style="width:35%"><?php _e( 'Название', 'meal-menu' ); ?></th><th style="width:25%"><?php _e( 'С', 'meal-menu' ); ?></th><th style="width:25%"><?php _e( 'По', 'meal-menu' ); ?></th><th style="width:15%"></th></tr>
			</thead>
			<tbody id="vac-body"></tbody>
		</table>

		<div class="vac-actions">
			<button type="button" class="btn btn-outline btn-sm" id="btn-add-vac">+ <?php _e( 'Добавить каникулы', 'meal-menu' ); ?></button>
			<button type="button" class="btn btn-outline btn-sm" id="btn-fill-default-vac"><?php _e( 'Типовые каникулы', 'meal-menu' ); ?></button>
		</div>
	</div>

	<!-- Утверждающее лицо -->
	<div class="panel school-only-section">
		<div class="panel-title"><?php _e( 'Типовое примерное меню — утверждающее лицо', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2"><?php _e( 'ФИО и должность отображаются в шапке файла типового меню (tm-файл).', 'meal-menu' ); ?></p>
		<div class="form-group">
			<label for="tm-approver-position"><?php _e( 'Должность', 'meal-menu' ); ?></label>
			<input type="text" id="tm-approver-position" class="form-control"
				value="<?php echo esc_attr( $settings['tm_approver_position'] ?? '' ); ?>"
				placeholder="<?php esc_attr_e( 'Например: Директор школы', 'meal-menu' ); ?>">
		</div>
		<div class="form-group">
			<label for="tm-approver-name"><?php _e( 'ФИО', 'meal-menu' ); ?></label>
			<input type="text" id="tm-approver-name" class="form-control"
				value="<?php echo esc_attr( $settings['tm_approver_name'] ?? '' ); ?>"
				placeholder="<?php esc_attr_e( 'Например: Иванова А.Б.', 'meal-menu' ); ?>">
		</div>
	</div>

	<div class="mt-2" style="text-align:right">
		<button type="button" class="btn btn-primary" id="btn-save"><?php _e( 'Сохранить настройки', 'meal-menu' ); ?> <span id="btn-msg" class="btn-msg"></span></button>
	</div>
</div>

<div id="meal-dialog" class="meal-modal-overlay dialog" style="display:none">
	<div class="meal-dialog-box">
		<p id="meal-dialog-text"></p>
		<div class="meal-dialog-actions">
			<button type="button" class="btn btn-outline" id="meal-dialog-cancel"><?php _e( 'Отмена', 'meal-menu' ); ?></button>
			<button type="button" class="btn btn-primary" id="meal-dialog-ok"><?php _e( 'OK', 'meal-menu' ); ?></button>
		</div>
	</div>
</div>

<script>
(function($) {
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';
	var btnSave = document.getElementById('btn-save');
	var btnMsg = document.getElementById('btn-msg');

	function apiPost(data, cb) {
		data.nonce = nonce;
		$.post(ajaxUrl + '?action=meal_save_settings', JSON.stringify(data), function(r) {
			try { cb(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { cb({ok:false, error: 'Ошибка сервера'}); }
		}).fail(function() { cb({ok:false, error: 'Сетевая ошибка'}); });
	}

	function showMsg(text, isError) {
		btnMsg.innerHTML = '<span style="color:' + (isError ? 'var(--error, #a02020)' : 'var(--success, #4a7a2a)') + '">' + text + '</span>';
		setTimeout(function() { btnMsg.innerHTML = ''; }, 4000);
	}

	document.getElementById('dept-list').addEventListener('change', function(e) {
		if (e.target.classList.contains('dept-toggle')) {
			var card = e.target.closest('.dept-card');
			var enabled = e.target.checked;
			card.classList.toggle('enabled', enabled);
			var code = card.dataset.code;
			var label = card.querySelector('.dept-title').textContent.trim();

			if (!enabled) {
				// Remove this department from all other dropdown options
				document.querySelectorAll('.sel-merge').forEach(function(sel) {
					var opt = sel.querySelector('option[value="' + code + '"]');
					if (opt) {
						if (opt.selected) {
							sel.value = '';
							sel._prev = '';
							var tCard = document.querySelector('.dept-card[data-code="' + code + '"]');
							if (tCard) {
								tCard.querySelector('.merge-select-row').style.display = '';
								tCard.querySelector('.merge-static-row').style.display = 'none';
							}
						}
						sel.removeChild(opt);
					}
				});
				// Clear this department's own merge and restore its target
				var ownSel = card.querySelector('.sel-merge');
				if (ownSel && ownSel.value) {
					var tCode = ownSel.value;
					ownSel.value = '';
					ownSel._prev = '';
					var tCard = document.querySelector('.dept-card[data-code="' + tCode + '"]');
					if (tCard) {
						tCard.querySelector('.merge-select-row').style.display = '';
						tCard.querySelector('.merge-static-row').style.display = 'none';
					}
				}
				card.querySelector('.merge-select-row').style.display = '';
				card.querySelector('.merge-static-row').style.display = 'none';
				card.querySelectorAll('.wd-chk').forEach(function(cb) { cb.disabled = false; });
				var vac = card.querySelector('.chk-ignore-vac');
				if (vac) vac.disabled = false;
			} else {
				document.querySelectorAll('.sel-merge').forEach(function(sel) {
					if (sel.closest('.dept-card').dataset.code === code) return;
					if (sel.querySelector('option[value="' + code + '"]')) return;
					var opt = document.createElement('option');
					opt.value = code;
					opt.textContent = label;
					sel.insertBefore(opt, sel.options[1] || null);
				});
				// Sync disabled state on the re-enabled card itself
				var ownSel = card.querySelector('.sel-merge');
				if (ownSel && ownSel.value) {
					card.querySelectorAll('.wd-chk').forEach(function(cb) { cb.disabled = true; });
					var vac = card.querySelector('.chk-ignore-vac');
					if (vac) vac.disabled = true;
				}
			}
		}
	});

	// Real-time merge toggling
	document.querySelectorAll('.sel-merge').forEach(function(sel) {
		sel._prev = sel.value;
	});

	document.getElementById('dept-list').addEventListener('change', function(e) {
		if (!e.target.classList.contains('sel-merge')) return;
		var sel = e.target;
		var card = sel.closest('.dept-card');
		var prevCode = sel._prev;
		var newCode = sel.value;

		// Restore previously selected target if no other select points to it
		if (prevCode) {
			var stillReferenced = false;
			document.querySelectorAll('.sel-merge').forEach(function(s) {
				if (s !== sel && s.value === prevCode) stillReferenced = true;
			});
			if (!stillReferenced) {
				var prevCard = document.querySelector('.dept-card[data-code="' + prevCode + '"]');
				if (prevCard) {
					prevCard.querySelector('.merge-select-row').style.display = '';
					prevCard.querySelector('.merge-static-row').style.display = 'none';
				}
			}
		}

		// Disable/enable calendar fields in source department
		function setMergeDisabled(card, disabled) {
			card.querySelectorAll('.wd-chk').forEach(function(cb) { cb.disabled = disabled; });
			var vac = card.querySelector('.chk-ignore-vac');
			if (vac) vac.disabled = disabled;
		}

		if (newCode) {
			setMergeDisabled(card, true);
			var targetCard = document.querySelector('.dept-card[data-code="' + newCode + '"]');
			if (targetCard) {
				targetCard.querySelector('.merge-select-row').style.display = 'none';
				var sources = [];
				document.querySelectorAll('.sel-merge').forEach(function(s) {
					if (s.value === newCode) {
						var srcCard = s.closest('.dept-card');
						var srcName = (srcCard.querySelector('.inp-dept-name').value || srcCard.querySelector('.dept-title').textContent).trim();
						sources.push(srcName);
					}
				});
				targetCard.querySelector('.merge-static-text').textContent = 'Источник данных для: ' + sources.join(', ');
				targetCard.querySelector('.merge-static-row').style.display = '';
			}
		} else if (prevCode) {
			setMergeDisabled(card, false);
		}

		sel._prev = newCode;
	});

	// Toggle suffix row with publish checkbox
	document.getElementById('dept-list').addEventListener('change', function(e) {
		if (e.target.classList.contains('chk-publish')) {
			var card = e.target.closest('.dept-card');
			var suffixRow = card.querySelector('.suffix-row');
			if (suffixRow) suffixRow.style.display = e.target.checked ? '' : 'none';
		}
		if (e.target.classList.contains('chk-camp')) {
			var card = e.target.closest('.dept-card');
			var campFields = card.querySelector('.camp-fields');
			if (campFields) campFields.style.display = e.target.checked ? '' : 'none';
		}
	});

	var addForm = document.getElementById('add-dept-form');
	document.getElementById('btn-add-dept').addEventListener('click', function() { addForm.classList.toggle('show'); });
	document.getElementById('btn-cancel-add').addEventListener('click', function() { addForm.classList.remove('show'); });
	document.getElementById('btn-confirm-add').addEventListener('click', function() {
		var code = document.getElementById('new-dept-code').value.trim();
		var label = document.getElementById('new-dept-label').value.trim();
		var suffix = document.getElementById('new-dept-suffix').value.trim();
		if (!code || !label) { showMsg('Укажите код и название', true); return; }
		if (!/^[a-z0-9_]+$/.test(code)) { showMsg('Код: только строчные латинские буквы, цифры, _', true); return; }
		apiPost({action: 'add_department', code: code, label: label, file_suffix: suffix}, function(r) {
			if (r.ok) location.reload();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	document.getElementById('dept-list').addEventListener('click', function(e) {
		var btn = e.target.closest('.btn-delete-dept');
		if (!btn) return;
		showConfirm('Удалить это отделение?', function() {
			apiPost({action: 'delete_department', id: parseInt(btn.dataset.id)}, function(r) {
				if (r.ok) location.reload();
				else showMsg(r.error || 'Ошибка', true);
			});
		});
	});

	var hasDepartments = <?php echo empty( $departments ) ? 'false' : 'true'; ?>;

	// ─── custom confirm ─────────────────────────────────
	var dialogOverlay = document.getElementById('meal-dialog');
	var dialogText = document.getElementById('meal-dialog-text');
	var dialogOk = document.getElementById('meal-dialog-ok');
	var dialogCancel = document.getElementById('meal-dialog-cancel');
	var dialogCallback = null;

	function showConfirm(msg, cb) {
		dialogText.textContent = msg;
		dialogOverlay.style.display = '';
		dialogCallback = cb;
	}
	dialogOk.addEventListener('click', function() {
		dialogOverlay.style.display = 'none';
		if (dialogCallback) dialogCallback();
		dialogCallback = null;
	});
	dialogCancel.addEventListener('click', function() {
		dialogOverlay.style.display = 'none';
		dialogCallback = null;
	});
	dialogOverlay.addEventListener('click', function(e) {
		if (e.target === dialogOverlay) {
			dialogOverlay.style.display = 'none';
			dialogCallback = null;
		}
	});

	// ─── transliteration ────────────────────────────────
	var cyrMap = {
		'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y',
		'к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f',
		'х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya',
		'А':'a','Б':'b','В':'v','Г':'g','Д':'d','Е':'e','Ё':'yo','Ж':'zh','З':'z','И':'i','Й':'y',
		'К':'k','Л':'l','М':'m','Н':'n','О':'o','П':'p','Р':'r','С':'s','Т':'t','У':'u','Ф':'f',
		'Х':'h','Ц':'ts','Ч':'ch','Ш':'sh','Щ':'sch','Ъ':'','Ы':'y','Ь':'','Э':'e','Ю':'yu','Я':'ya'
	};
	function transliterate(text) {
		return text.replace(/[а-яёА-ЯЁ]/g, function(ch) { return cyrMap[ch] || ch; }).replace(/[^a-zA-Z0-9_-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '').toLowerCase();
	}

	document.getElementById('new-dept-label').addEventListener('input', function() {
		var codeInput = document.getElementById('new-dept-code');
		if (!codeInput.value || codeInput.dataset.auto === '1') {
			codeInput.value = transliterate(this.value);
			codeInput.dataset.auto = '1';
		}
	});
	document.getElementById('new-dept-code').addEventListener('input', function() {
		this.dataset.auto = '0';
	});

	// ─── double-click guard ─────────────────────────────
	function guardFormSubmit(btn, cb) {
		btn.addEventListener('click', function guard(e) {
			if (btn.classList.contains('loading')) {
				e.stopImmediatePropagation();
				return;
			}
			btn.classList.add('loading');
			var orig = btn.textContent;
			btn.textContent = '⏳ ' + orig.trim();
			cb(function() {
				btn.classList.remove('loading');
				btn.textContent = orig;
			});
		});
	}

	function seedAndReload(instType) {
		apiPost({action: 'seed_default_departments', institution_type: instType}, function(r) {
			if (r.ok) { location.reload(); }
			else { showMsg(r.error || 'Ошибка', true); }
		});
	}

	document.getElementById('inst-type').addEventListener('change', function() {
		var instType = this.value;
		if (hasDepartments) {
			var label = instType === 'kindergarten' ? 'Детский сад' : 'Школа';
			showConfirm('Сменить тип учреждения на «' + label + '»? Это заменит все отделения на типовые.', function() {
				seedAndReload(instType);
			});
			this.value = instType === 'kindergarten' ? 'school' : 'kindergarten';
			return;
		}
		seedAndReload(instType);
	});

	function defaultDeptName(card) {
		var code = card.querySelector('.inp-dept-name').dataset.code;
		if (code === 'main') {
			var cards = document.querySelectorAll('.dept-card');
			for (var i = 0; i < cards.length; i++) {
				var inp = cards[i].querySelector('.inp-dept-name');
				if (inp && inp.dataset.code === 'ss') {
					return cards[i].querySelector('.dept-toggle').checked ? 'Основная школа' : 'Старшеклассники';
				}
			}
		}
		return card.querySelector('.dept-title').textContent.trim();
	}

	function toggleSchoolOnly() {
		var isKindergarten = document.getElementById('inst-type').value === 'kindergarten';
		document.querySelectorAll('.school-only-section').forEach(function(el) {
			el.style.display = isKindergarten ? 'none' : '';
		});
		document.querySelectorAll('.school-only-field').forEach(function(el) {
			el.style.display = isKindergarten ? 'none' : '';
		});
	}
	toggleSchoolOnly();

	if (!hasDepartments) {
		seedAndReload(document.getElementById('inst-type').value);
	}

	guardFormSubmit(document.getElementById('btn-save'), function(unlock) {
		var btn = document.getElementById('btn-save');
		var deps = [];
		document.querySelectorAll('.dept-card').forEach(function(card) {
			var wdChecks = card.querySelectorAll('.wd-chk:checked');
			var workdays = Array.from(wdChecks).map(function(c) { return parseInt(c.value); });
			var suffixInput = card.querySelector('.inp-suffix');
			var ignoreVacChk = card.querySelector('.chk-ignore-vac');
			var nameInput = card.querySelector('.inp-dept-name');
			var deptName = nameInput.value.trim() || defaultDeptName(card);
			var mergeSelect = card.querySelector('.sel-merge');

			var campObj = {};
			var campChk = card.querySelector('.chk-camp');
			if (campChk) {
				var campWd = card.querySelectorAll('.camp-wd-chk:checked');
				campObj = {
					has_summer_camp: campChk.checked ? 1 : 0,
					camp_start_date: campChk.checked ? (card.querySelector('.camp-start').value || null) : null,
					camp_end_date: campChk.checked ? (card.querySelector('.camp-end').value || null) : null,
					camp_workdays: campChk.checked ? Array.from(campWd).map(function(c) { return parseInt(c.value); }).join(',') : '1,2,3,4,5',
					camp_is_boarding: campChk.checked && card.querySelector('.camp-boarding').checked ? 1 : 0,
					camp_publish_xlsx: campChk.checked && card.querySelector('.camp-publish').checked ? 1 : 0,
				};
			}

			deps.push({
				id: parseInt(card.dataset.id),
				is_enabled: card.querySelector('.dept-toggle').checked ? 1 : 0,
				dept_name: deptName,
				workdays: workdays.join(','),
				note: card.querySelector('.inp-dept-note') ? card.querySelector('.inp-dept-note').value : undefined,
				is_boarding: card.querySelector('.chk-boarding').checked ? 1 : 0,
				publish_xlsx: card.querySelector('.chk-publish').checked ? 1 : 0,
				ignore_vacations: ignoreVacChk && ignoreVacChk.checked ? 1 : 0,
				file_suffix: suffixInput ? suffixInput.value : undefined,
				merged_with: mergeSelect ? mergeSelect.value || '' : undefined,
				has_summer_camp: campObj.has_summer_camp !== undefined ? campObj.has_summer_camp : undefined,
				camp_start_date: campObj.camp_start_date !== undefined ? campObj.camp_start_date : undefined,
				camp_end_date: campObj.camp_end_date !== undefined ? campObj.camp_end_date : undefined,
				camp_workdays: campObj.camp_workdays !== undefined ? campObj.camp_workdays : undefined,
				camp_is_boarding: campObj.camp_is_boarding !== undefined ? campObj.camp_is_boarding : undefined,
				camp_publish_xlsx: campObj.camp_publish_xlsx !== undefined ? campObj.camp_publish_xlsx : undefined,
			});
		});
		apiPost({
			action: 'save_settings',
			org_name: document.getElementById('org-name').value,
			institution_type: document.getElementById('inst-type').value,
			tm_approver_position: document.getElementById('tm-approver-position').value,
			tm_approver_name: document.getElementById('tm-approver-name').value,
			academic_year_start: ddmmToMmdd(document.getElementById('ay-start').value.trim()),
			academic_year_end: ddmmToMmdd(document.getElementById('ay-end').value.trim()),
			reset_cycle_after_vacation: document.getElementById('ay-reset').checked ? 1 : 0,
			departments: deps
		}, function(r) {
			unlock();
			if (r.ok) {
				showMsg('Настройки сохранены.', false);
			} else {
				showMsg(r.error || 'Ошибка сохранения', true);
			}
		});
	});

	var vacYear = document.getElementById('vac-year');
	var vacBody = document.getElementById('vac-body');
	var holidayBody = document.getElementById('holiday-body-admin');

	function mmddToDdmm(v) {
		if (!v || v.indexOf('-') < 0) return v;
		var p = v.split('-');
		return p[1] + '.' + p[0];
	}
	function ddmmToMmdd(v) {
		if (!v || v.indexOf('.') < 0) return v;
		var p = v.split('.');
		return p[1] + '-' + p[0];
	}

	document.getElementById('ay-start').value = mmddToDdmm(<?php echo json_encode( $ay_settings['academic_year_start'] ); ?>);
	document.getElementById('ay-end').value = mmddToDdmm(<?php echo json_encode( $ay_settings['academic_year_end'] ); ?>);

	// ─── Каникулы ───────────────────────────────────────────

	(function() {
		var now = new Date();
		var curYear = now.getFullYear();
		var m = now.getMonth() + 1;
		var startY = m >= 8 ? curYear : curYear - 1;
		for (var y = startY + 1; y >= startY - 2; y--) {
			var opt = document.createElement('option');
			opt.value = y + '-' + (y + 1);
			opt.textContent = y + '/' + ('' + (y + 1)).slice(-2);
			vacYear.appendChild(opt);
		}
		vacYear.value = startY + '-' + (startY + 1);
		loadVacations();
	})();

	vacYear.addEventListener('change', loadVacations);

	function loadVacations() {
		apiPost({action: 'get_vacations', academic_year: vacYear.value}, function(r) {
			if (!r.ok) return;
			renderVacations(r.vacations);
		});
	}

	function ymdToDmy(v) {
		if (!v || v.indexOf('-') < 0) return v;
		var p = v.split('-');
		return p[2] + '.' + p[1] + '.' + p[0];
	}
	function dmyToYmd(v) {
		if (!v || v.indexOf('.') < 0) return v;
		var p = v.split('.');
		return p[2] + '-' + p[1] + '-' + p[0];
	}

	function renderVacationRow(v) {
		var tr = document.createElement('tr');
		tr.dataset.id = v.id;
		tr.innerHTML =
			'<td><input type="text" class="vac-label" value="' + escHtml(v.label) + '"></td>' +
			'<td><input type="text" class="vac-from" value="' + ymdToDmy(v.date_from) + '" placeholder="ДД.ММ.ГГГГ" style="width:100px"></td>' +
			'<td><input type="text" class="vac-to" value="' + ymdToDmy(v.date_to) + '" placeholder="ДД.ММ.ГГГГ" style="width:100px"></td>' +
			'<td style="text-align:right"><button class="btn-del-vac" title="Удалить">&times;</button></td>';
		return tr;
	}

	function saveVacationRow(tr) {
		var id = parseInt(tr.dataset.id);
		apiPost({
			action: 'update_vacation',
			id: id,
			label: tr.querySelector('.vac-label').value,
			date_from: dmyToYmd(tr.querySelector('.vac-from').value),
			date_to: dmyToYmd(tr.querySelector('.vac-to').value)
		}, function(r) {
			if (!r.ok) showMsg(r.error || 'Ошибка', true);
		});
	}

	function renderVacations(list) {
		vacBody.innerHTML = '';
		if (!list || list.length === 0) {
			vacBody.innerHTML = '<tr><td colspan="4" style="color:var(--wp-muted);text-align:center;padding:16px">Каникулы не заданы</td></tr>';
		} else {
			list.forEach(function(v) { vacBody.appendChild(renderVacationRow(v)); });
		}
	}

	vacBody.addEventListener('click', function(e) {
		var btn = e.target;
		var tr = btn.closest('tr');
		if (!tr || !btn.classList.contains('btn-del-vac')) return;
		var id = parseInt(tr.dataset.id);
		if (btn.dataset.confirm !== '1') {
			btn.dataset.confirm = '1';
			btn.textContent = 'Удалить';
			btn.style.color = '#c00';
			setTimeout(function() { delete btn.dataset.confirm; btn.textContent = '\u00d7'; btn.style.color = ''; }, 3000);
			return;
		}
		apiPost({action: 'delete_vacation', id: id}, function(r) {
			if (r.ok) loadVacations();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	vacBody.addEventListener('blur', function(e) {
		var inp = e.target;
		if (!inp.classList.contains('vac-label') && !inp.classList.contains('vac-from') && !inp.classList.contains('vac-to')) return;
		var tr = inp.closest('tr');
		if (!tr) return;
		saveVacationRow(tr);
	}, true);

	document.getElementById('btn-add-vac').addEventListener('click', function() {
		var label = prompt('Название каникул:', 'Каникулы');
		if (!label) return;
		var parts = vacYear.value.split('-');
		apiPost({
			action: 'add_vacation',
			academic_year: vacYear.value,
			label: label,
			date_from: parts[0] + '-10-28',
			date_to: parts[0] + '-11-05'
		}, function(r) {
			if (r.ok) loadVacations();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	document.getElementById('btn-fill-default-vac').addEventListener('click', function() {
		showConfirm('Добавить типовые каникулы для ' + vacYear.value + '? (Существующие не удаляются)', function() {
			apiPost({action: 'fill_default_vacations', academic_year: vacYear.value}, function(r) {
				if (r.ok) { renderVacations(r.vacations); showMsg('Типовые каникулы добавлены', false); }
				else { showMsg(r.error || 'Ошибка', true); }
			});
		});
	});

	// ─── Праздники ──────────────────────────────────────────

	function loadHolidays() {
		apiPost({action: 'get_holidays'}, function(r) {
			if (!r.ok) return;
			renderHolidays(r.holidays);
		});
	}

	function renderHolidayRow(h) {
		var tr = document.createElement('tr');
		tr.dataset.id = h.id;
		tr.style.background = '#fff8e1';
		if (h.month_day_to) {
			tr.innerHTML =
				'<td><input type="text" class="hol-label" value="' + escHtml(h.label) + '"></td>' +
				'<td><input type="text" class="hol-from" value="' + mmddToDdmm(h.month_day) + '" placeholder="ДД.ММ" style="width:70px"></td>' +
				'<td><input type="text" class="hol-to" value="' + mmddToDdmm(h.month_day_to) + '" placeholder="ДД.ММ" style="width:70px"></td>' +
				'<td style="text-align:right"><button class="btn-del-holiday" title="Удалить">&times;</button></td>';
		} else {
			tr.innerHTML =
				'<td><input type="text" class="hol-label" value="' + escHtml(h.label) + '"></td>' +
				'<td colspan="2"><input type="text" class="hol-from" value="' + mmddToDdmm(h.month_day) + '" placeholder="ДД.ММ" style="width:90px"></td>' +
				'<td style="text-align:right"><button class="btn-del-holiday" title="Удалить">&times;</button></td>';
		}
		return tr;
	}

	function renderHolidays(list) {
		holidayBody.innerHTML = '';
		if (!list || list.length === 0) {
			holidayBody.innerHTML = '<tr><td colspan="4" style="color:var(--wp-muted);text-align:center;padding:16px">Праздники не заданы</td></tr>';
		} else {
			list.forEach(function(h) { holidayBody.appendChild(renderHolidayRow(h)); });
		}
	}

	holidayBody.addEventListener('click', function(e) {
		var btn = e.target;
		if (!btn.classList.contains('btn-del-holiday')) return;
		var tr = btn.closest('tr');
		if (!tr) return;
		var id = parseInt(tr.dataset.id);
		if (btn.dataset.confirm !== '1') {
			btn.dataset.confirm = '1';
			btn.textContent = 'Удалить';
			btn.style.color = '#c00';
			setTimeout(function() { delete btn.dataset.confirm; btn.textContent = '\u00d7'; btn.style.color = ''; }, 3000);
			return;
		}
		apiPost({action: 'delete_holiday', id: id}, function(r) {
			if (r.ok) loadHolidays();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	document.getElementById('btn-add-holiday-admin').addEventListener('click', function() {
		var label = prompt('Название праздника:', 'Праздник');
		if (!label) return;
		var fromStr = prompt('Дата начала (ДД.ММ):', '01.01');
		if (!fromStr) return;
		var toStr = prompt('Дата окончания (ДД.ММ, оставьте пустым для одного дня):', '');
		var monthDay = ddmmToMmdd(fromStr);
		if (!monthDay || monthDay.indexOf('-') < 0) { showMsg('Неверный формат даты', true); return; }
		var monthDayTo = toStr ? ddmmToMmdd(toStr) : null;
		if (monthDayTo && (monthDayTo.indexOf('-') < 0)) { showMsg('Неверный формат даты окончания', true); return; }
		apiPost({
			action: 'add_holiday',
			label: label,
			month_day: monthDay,
			month_day_to: monthDayTo
		}, function(r) {
			if (r.ok) loadHolidays();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	document.getElementById('btn-fill-holidays').addEventListener('click', function() {
		showConfirm('Добавить государственные праздники РФ?', function() {
			apiPost({action: 'fill_default_holidays'}, function(r) {
				if (r.ok) { renderHolidays(r.holidays); showMsg('Государственные праздники добавлены', false); }
				else { showMsg(r.error || 'Ошибка', true); }
			});
		});
	});

	loadHolidays();

	function escHtml(s) { return $('<div>').text(s).html(); }
})(jQuery);
</script>
