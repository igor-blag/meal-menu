<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$settings     = $db->get_kitchen_settings();
$departments  = $db->get_all_departments();
$ay_settings  = $db->get_academic_year_settings();

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
	</div>

	<!-- Отделения -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Отделения', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2"><?php _e( 'Отметьте отделения вашей организации и настройте параметры каждого.', 'meal-menu' ); ?></p>

		<div id="dept-list">
		<?php foreach ( $departments as $d ):
			$wd = explode( ',', $d['workdays'] );
		?>
			<div class="dept-card<?php echo $d['is_enabled'] ? ' enabled' : ''; ?>" data-id="<?php echo (int) $d['id']; ?>">
				<div class="dept-header">
					<input type="checkbox" class="dept-toggle"<?php echo $d['is_enabled'] ? ' checked' : ''; ?>>
					<span class="dept-title"><?php echo esc_html( $d['label'] ); ?></span>
					<?php if ( ! $d['is_builtin'] ): ?>
						<span class="badge-custom"><?php _e( 'кастомное', 'meal-menu' ); ?></span>
						<button type="button" class="btn-delete-dept" data-id="<?php echo (int) $d['id']; ?>" title="<?php esc_attr_e( 'Удалить отделение', 'meal-menu' ); ?>">&times; <?php _e( 'Удалить', 'meal-menu' ); ?></button>
					<?php endif; ?>
					<?php if ( $d['note'] ): ?>
						<span class="dept-note"><?php echo esc_html( $d['note'] ); ?></span>
					<?php endif; ?>
				</div>
				<div class="dept-body">
					<div class="form-row">
						<div class="field">
							<label><?php _e( 'Полное название', 'meal-menu' ); ?></label>
							<input type="text" class="inp-label" value="<?php echo esc_attr( $d['label'] ); ?>">
						</div>
						<div class="field">
							<label><?php _e( 'Краткое (для вкладок)', 'meal-menu' ); ?></label>
							<input type="text" class="inp-label-short" value="<?php echo esc_attr( $d['label_short'] ); ?>">
						</div>
						<div class="field">
							<label><?php _e( 'Отд./корпус', 'meal-menu' ); ?></label>
							<input type="text" class="inp-dept-name" value="<?php echo esc_attr( $d['dept_name'] ); ?>" placeholder="<?php esc_attr_e( 'Корпус 1', 'meal-menu' ); ?>">
						</div>
					</div>

					<div class="form-row">
						<div class="field">
							<label><?php _e( 'Рабочие дни', 'meal-menu' ); ?></label>
							<div class="wd-group">
								<?php foreach ( array( 1 => 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) as $i => $name ): ?>
								<label class="wd-label">
									<input type="checkbox" class="wd-chk" value="<?php echo $i; ?>"
										<?php echo in_array( (string) $i, $wd, true ) ? 'checked' : ''; ?>>
									<?php echo $name; ?>
								</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>


				
					<div class="opt-row">
						<label class="opt-label<?php echo $d['code'] === 'preschool' ? ' disabled' : ''; ?>">
							<input type="checkbox" class="chk-boarding"
								<?php echo $d['is_boarding'] ? 'checked' : ''; ?>
								<?php echo $d['code'] === 'preschool' ? 'disabled checked' : ''; ?>>
							<?php _e( 'Интернат (доп. приёмы пищи)', 'meal-menu' ); ?>
						</label>
						<label class="opt-label">
							<input type="checkbox" class="chk-publish"
								<?php echo $d['publish_xlsx'] ? 'checked' : ''; ?>>
							<?php _e( 'Публикация файлов меню', 'meal-menu' ); ?>
						</label>
						<label class="opt-label">
							<input type="checkbox" class="chk-ignore-vac"
								<?php echo ! empty( $d['ignore_vacations'] ) ? 'checked' : ''; ?>>
							<?php _e( 'Без каникул (круглый год)', 'meal-menu' ); ?>
						</label>
					</div>

					<div class="form-row">
						<div class="field" style="max-width:300px">
							<label><?php _e( 'Постфикс файлов', 'meal-menu' ); ?></label>
							<?php if ( $d['is_builtin'] ): ?>
								<span class="suffix-display"><?php echo $d['file_suffix'] ?: __( '(без постфикса)', 'meal-menu' ); ?> &rarr; ГГГГ-ММ-ДД<?php echo esc_html( $d['file_suffix'] ); ?>.xlsx</span>
							<?php else: ?>
								<input type="text" class="inp-suffix" value="<?php echo esc_attr( $d['file_suffix'] ); ?>" placeholder="-custom">
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
		</div>

		<div class="mt-2">
			<button type="button" class="btn btn-outline btn-sm" id="btn-add-dept">+ <?php _e( 'Добавить отделение', 'meal-menu' ); ?></button>
		</div>
		<div class="custom-dept-form" id="add-dept-form">
			<div class="form-row">
				<div class="field">
					<label><?php _e( 'Код (латиница, без пробелов)', 'meal-menu' ); ?></label>
					<input type="text" id="new-dept-code" class="form-control" placeholder="nursery" pattern="[a-z0-9_]+">
				</div>
				<div class="field">
					<label><?php _e( 'Название', 'meal-menu' ); ?></label>
					<input type="text" id="new-dept-label" class="form-control" placeholder="<?php esc_attr_e( 'Ясельная группа', 'meal-menu' ); ?>">
				</div>
				<div class="field">
					<label><?php _e( 'Постфикс файлов', 'meal-menu' ); ?></label>
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
	<div class="panel">
		<div class="panel-title"><?php _e( 'Учебный год и каникулы', 'meal-menu' ); ?></div>

		<div class="ay-fields">
			<div class="field">
				<label><?php _e( 'Начало уч. года (ММ-ДД)', 'meal-menu' ); ?></label>
				<input type="text" id="ay-start" value="<?php echo esc_attr( $ay_settings['academic_year_start'] ); ?>" placeholder="09-01">
			</div>
			<div class="field">
				<label><?php _e( 'Конец уч. года (ММ-ДД)', 'meal-menu' ); ?></label>
				<input type="text" id="ay-end" value="<?php echo esc_attr( $ay_settings['academic_year_end'] ); ?>" placeholder="05-31">
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

		<table class="vac-table">
			<thead>
				<tr><th style="width:35%"><?php _e( 'Название', 'meal-menu' ); ?></th><th style="width:25%"><?php _e( 'С', 'meal-menu' ); ?></th><th style="width:25%"><?php _e( 'По', 'meal-menu' ); ?></th><th style="width:15%"></th></tr>
			</thead>
			<tbody id="vac-body"></tbody>
		</table>

		<div class="vac-actions">
			<button type="button" class="btn btn-outline btn-sm" id="btn-add-vac">+ <?php _e( 'Добавить каникулы', 'meal-menu' ); ?></button>
			<button type="button" class="btn btn-outline btn-sm" id="btn-fill-default"><?php _e( 'Типовые каникулы РФ', 'meal-menu' ); ?></button>
		</div>
	</div>

	<!-- Утверждающее лицо -->
	<div class="panel">
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

	<!-- Публикация файлов -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Публикация XLSX-файлов', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2">
			<?php _e( 'Путь к папке, куда копируются сгенерированные Excel-файлы для доступа из внешних систем (мониторинг ФЦМПО). По умолчанию — папка /food/ в корне сайта.', 'meal-menu' ); ?>
		</p>
		<div class="form-group">
			<label for="meal_food_dir"><?php _e( 'Путь к папке публикации', 'meal-menu' ); ?></label>
			<input type="text" id="meal_food_dir" class="form-control"
				value="<?php echo esc_attr( get_option( 'meal_food_dir', '' ) ); ?>"
				placeholder="/food">
			<p class="text-muted" style="margin-top:4px;font-size:.78rem">
				<?php _e( 'Оставьте пустым — файлы будут доступны по адресу:', 'meal-menu' ); ?>
				<code><?php echo esc_url( home_url( '/food/' ) ); ?></code>
			</p>
		</div>
	</div>

	<!-- Email -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Email-уведомления', 'meal-menu' ); ?></div>
		<div class="form-group">
			<label for="meal_admin_email"><?php _e( 'Email администратора (на него приходят напоминания)', 'meal-menu' ); ?></label>
			<input type="email" id="meal_admin_email" class="form-control"
				value="<?php echo esc_attr( get_option( 'meal_admin_email', '' ) ); ?>"
				placeholder="admin@school.ru">
		</div>
		<div class="form-group">
			<label for="meal_mail_from"><?php _e( 'Email отправителя', 'meal-menu' ); ?></label>
			<input type="email" id="meal_mail_from" class="form-control"
				value="<?php echo esc_attr( get_option( 'meal_mail_from', 'noreply@school.ru' ) ); ?>"
				placeholder="noreply@school.ru">
		</div>
		<div class="form-group">
			<label for="meal_mail_from_name"><?php _e( 'Имя отправителя', 'meal-menu' ); ?></label>
			<input type="text" id="meal_mail_from_name" class="form-control"
				value="<?php echo esc_attr( get_option( 'meal_mail_from_name', 'Мониторинг питания' ) ); ?>">
		</div>
		<hr>
		<p class="text-muted mb-2"><?php _e( 'SMTP (оставьте пустым для использования стандартной функции wp_mail).', 'meal-menu' ); ?></p>
		<div style="display:flex;gap:12px;flex-wrap:wrap">
			<div class="field" style="flex:2;min-width:200px">
				<label><?php _e( 'SMTP-хост', 'meal-menu' ); ?></label>
				<input type="text" id="meal_smtp_host" class="form-control" value="<?php echo esc_attr( get_option( 'meal_smtp_host', '' ) ); ?>" placeholder="smtp.example.com">
			</div>
			<div class="field" style="flex:1;min-width:100px">
				<label><?php _e( 'Порт', 'meal-menu' ); ?></label>
				<input type="number" id="meal_smtp_port" class="form-control" value="<?php echo esc_attr( get_option( 'meal_smtp_port', '587' ) ); ?>">
			</div>
			<div class="field" style="flex:1;min-width:100px">
				<label><?php _e( 'SMTP-пользователь', 'meal-menu' ); ?></label>
				<input type="text" id="meal_smtp_user" class="form-control" value="<?php echo esc_attr( get_option( 'meal_smtp_user', '' ) ); ?>">
			</div>
			<div class="field" style="flex:1;min-width:100px">
				<label><?php _e( 'SMTP-пароль', 'meal-menu' ); ?></label>
				<input type="password" id="meal_smtp_pass" class="form-control" value="<?php echo esc_attr( get_option( 'meal_smtp_pass', '' ) ); ?>">
			</div>
			<div class="field" style="flex:1;min-width:100px">
				<label><?php _e( 'SMTP-шифрование', 'meal-menu' ); ?></label>
				<select id="meal_smtp_secure" style="width:100%;padding:6px 10px;font-family:Georgia,serif;font-size:.9rem;border:1px solid var(--border-light);border-radius:var(--radius);color:var(--text)">
					<option value=""<?php selected( get_option( 'meal_smtp_secure', '' ), '' ); ?>><?php _e( 'Нет', 'meal-menu' ); ?></option>
					<option value="tls"<?php selected( get_option( 'meal_smtp_secure', '' ), 'tls' ); ?>>TLS</option>
					<option value="ssl"<?php selected( get_option( 'meal_smtp_secure', '' ), 'ssl' ); ?>>SSL</option>
				</select>
			</div>
		</div>
	</div>

	<div class="mt-2" style="text-align:right">
		<button type="button" class="btn btn-primary" id="btn-save"><?php _e( 'Сохранить настройки', 'meal-menu' ); ?></button>
	</div>
</div>

<script>
(function($) {
	var msgEl = document.getElementById('meal-msg');
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';

	function apiPost(data, cb) {
		data.nonce = nonce;
		$.post(ajaxUrl + '?action=meal_save_settings', JSON.stringify(data), function(r) {
			try { cb(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { cb({ok:false, error: 'Ошибка сервера'}); }
		}).fail(function() { cb({ok:false, error: 'Сетевая ошибка'}); });
	}

	function showMsg(text, isError) {
		msgEl.innerHTML = '<div class="alert ' + (isError ? 'alert-error' : 'alert-success') + '">' + text + '</div>';
		setTimeout(function() { msgEl.innerHTML = ''; }, 4000);
	}

	// Тоггл карточек
	document.getElementById('dept-list').addEventListener('change', function(e) {
		if (e.target.classList.contains('dept-toggle')) {
			var card = e.target.closest('.dept-card');
			card.classList.toggle('enabled', e.target.checked);
		}
	});

	// Добавление кастомного отделения
	var addForm = document.getElementById('add-dept-form');
	document.getElementById('btn-add-dept').addEventListener('click', function() { addForm.classList.toggle('show'); });
	document.getElementById('btn-cancel-add').addEventListener('click', function() { addForm.classList.remove('show'); });
	document.getElementById('btn-confirm-add').addEventListener('click', function() {
		var code = document.getElementById('new-dept-code').value.trim();
		var label = document.getElementById('new-dept-label').value.trim();
		var suffix = document.getElementById('new-dept-suffix').value.trim();
		if (!code || !label) { alert('Укажите код и название'); return; }
		if (!/^[a-z0-9_]+$/.test(code)) { alert('Код: только строчные латинские буквы, цифры, _'); return; }
		apiPost({action: 'add_department', code: code, label: label, file_suffix: suffix}, function(r) {
			if (r.ok) location.reload();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	// Удаление кастомного отделения
	document.getElementById('dept-list').addEventListener('click', function(e) {
		var btn = e.target.closest('.btn-delete-dept');
		if (!btn) return;
		if (!confirm('Удалить это отделение?')) return;
		apiPost({action: 'delete_department', id: parseInt(btn.dataset.id)}, function(r) {
			if (r.ok) location.reload();
			else showMsg(r.error || 'Ошибка', true);
		});
	});

	// Сохранение
	document.getElementById('btn-save').addEventListener('click', function() {
		var deps = [];
		document.querySelectorAll('.dept-card').forEach(function(card) {
			var wdChecks = card.querySelectorAll('.wd-chk:checked');
			var workdays = Array.from(wdChecks).map(function(c) { return parseInt(c.value); });
			var suffixInput = card.querySelector('.inp-suffix');
			var ignoreVacChk = card.querySelector('.chk-ignore-vac');
			deps.push({
				id: parseInt(card.dataset.id),
				is_enabled: card.querySelector('.dept-toggle').checked ? 1 : 0,
				label: card.querySelector('.inp-label').value,
				label_short: card.querySelector('.inp-label-short').value,
				dept_name: card.querySelector('.inp-dept-name').value,
				workdays: workdays.join(','),
				is_boarding: card.querySelector('.chk-boarding').checked ? 1 : 0,
				publish_xlsx: card.querySelector('.chk-publish').checked ? 1 : 0,
				ignore_vacations: ignoreVacChk && ignoreVacChk.checked ? 1 : 0,
				file_suffix: suffixInput ? suffixInput.value : undefined
			});
		});
		apiPost({
			action: 'save_settings',
			org_name: document.getElementById('org-name').value,
			meal_food_dir: document.getElementById('meal_food_dir').value,
			tm_approver_position: document.getElementById('tm-approver-position').value,
			tm_approver_name: document.getElementById('tm-approver-name').value,
			academic_year_start: document.getElementById('ay-start').value.trim(),
			academic_year_end: document.getElementById('ay-end').value.trim(),
			reset_cycle_after_vacation: document.getElementById('ay-reset').checked ? 1 : 0,
			departments: deps
		}, function(r) {
			if (r.ok) {
				showMsg('Настройки сохранены.', false);
				window.scrollTo({top: 0, behavior: 'smooth'});
			} else {
				showMsg(r.error || 'Ошибка сохранения', true);
			}
		});
	});

	// ─── Каникулы ───────────────────────────────────────────────
	var vacYear = document.getElementById('vac-year');
	var vacBody = document.getElementById('vac-body');

	(function() {
		var now = new Date();
		var curYear = now.getFullYear();
		var m = now.getMonth() + 1;
		var startY = m >= 8 ? curYear : curYear - 1;
		for (var y = startY + 1; y >= startY - 2; y--) {
			var opt = document.createElement('option');
			opt.value = y + '-' + (y + 1);
			opt.textContent = y + '-' + (y + 1);
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

	function renderVacations(list) {
		vacBody.innerHTML = '';
		if (!list || list.length === 0) {
			vacBody.innerHTML = '<tr><td colspan="4" style="color:var(--muted);text-align:center;padding:16px">Каникулы не заданы</td></tr>';
			return;
		}
		list.forEach(function(v) {
			var isHoliday = v.date_from === v.date_to;
			var tr = document.createElement('tr');
			tr.dataset.id = v.id;
			if (isHoliday) tr.style.background = '#fff8e1';
			tr.innerHTML =
				'<td><input type="text" class="vac-label" value="' + escHtml(v.label) + '">' +
				(isHoliday ? '<span style="font-size:.65rem;background:#ffc107;color:#333;padding:1px 6px;border-radius:3px;margin-left:4px;vertical-align:middle">праздник</span>' : '') +
				'</td>' +
				'<td><input type="date" class="vac-from" value="' + v.date_from + '"></td>' +
				'<td><input type="date" class="vac-to" value="' + v.date_to + '"></td>' +
				'<td style="text-align:right"><button class="btn-del-vac" title="Удалить">&times;</button>' +
				'<button class="btn btn-outline btn-sm vac-save-btn" style="margin-left:4px;padding:2px 8px;font-size:.78rem">OK</button></td>';
			vacBody.appendChild(tr);
		});
	}

	vacBody.addEventListener('click', function(e) {
		var btn = e.target;
		var tr = btn.closest('tr');
		if (!tr) return;
		var id = parseInt(tr.dataset.id);
		if (btn.classList.contains('btn-del-vac')) {
			if (!confirm('Удалить эти каникулы?')) return;
			apiPost({action: 'delete_vacation', id: id}, function(r) {
				if (r.ok) loadVacations();
				else showMsg(r.error || 'Ошибка', true);
			});
		}
		if (btn.classList.contains('vac-save-btn')) {
			apiPost({
				action: 'update_vacation',
				id: id,
				label: tr.querySelector('.vac-label').value,
				date_from: tr.querySelector('.vac-from').value,
				date_to: tr.querySelector('.vac-to').value
			}, function(r) {
				if (r.ok) showMsg('Сохранено', false);
				else showMsg(r.error || 'Ошибка', true);
			});
		}
	});

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

	document.getElementById('btn-fill-default').addEventListener('click', function() {
		if (!confirm('Добавить типовые каникулы для ' + vacYear.value + '?\n(Существующие не удаляются)')) return;
		apiPost({action: 'fill_default_vacations', academic_year: vacYear.value}, function(r) {
			if (r.ok) { renderVacations(r.vacations); showMsg('Типовые каникулы добавлены', false); }
			else { showMsg(r.error || 'Ошибка', true); }
		});
	});

	function escHtml(s) { return $('<div>').text(s).html(); }
})(jQuery);
</script>
