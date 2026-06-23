<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Настройки плагина', 'meal-menu' ); ?></h1>

	<div id="meal-msg"></div>

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

	<!-- AI prompt -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'AI-промт для распознавания фото', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2">
			<?php _e( 'Промт отправляется AI-модели вместе с фотографией меню. Вы можете отредактировать его под свои нужды. Сброс восстанавливает значение по умолчанию.', 'meal-menu' ); ?>
		</p>
		<div class="form-group">
			<textarea id="meal_ai_prompt" class="form-control" style="font-family:monospace;min-height:280px;white-space:pre-wrap;tab-size:2;line-height:1.5;font-size:.85rem"
				><?php echo esc_textarea( \Meal_Menu\Importer_Photo::build_prompt() ); ?></textarea>
		</div>
		<div style="display:flex;gap:8px">
			<button type="button" class="btn btn-outline btn-sm" id="btn-ai-prompt-reset"><?php _e( '↺ Сбросить на умолчание', 'meal-menu' ); ?></button>
			<span id="ai-prompt-msg" class="btn-msg"></span>
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
				<select id="meal_smtp_secure" style="width:100%;padding:6px 10px;font-size:.9rem;border:1px solid var(--wp-border);border-radius:var(--wp-radius);color:var(--wp-text)">
					<option value=""<?php selected( get_option( 'meal_smtp_secure', '' ), '' ); ?>><?php _e( 'Нет', 'meal-menu' ); ?></option>
					<option value="tls"<?php selected( get_option( 'meal_smtp_secure', '' ), 'tls' ); ?>>TLS</option>
					<option value="ssl"<?php selected( get_option( 'meal_smtp_secure', '' ), 'ssl' ); ?>>SSL</option>
				</select>
			</div>
		</div>
	</div>

	<div class="mt-2" style="text-align:right">
		<button type="button" class="btn btn-primary" id="btn-save"><?php _e( 'Сохранить настройки', 'meal-menu' ); ?> <span id="btn-msg" class="btn-msg"></span></button>
	</div>

	<hr style="margin-top:32px">

	<!-- Импорт/Экспорт -->
	<div class="panel">
		<div class="panel-title"><?php _e( 'Импорт / Экспорт данных', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2">
			<?php _e( 'Экспорт сохраняет все данные плагина в JSON-файл. Импорт восстанавливает данные из такого файла (полная замена).', 'meal-menu' ); ?>
		</p>
		<div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
			<div>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=meal_export_data' ), 'meal_export_data' ) ); ?>"
				   class="btn btn-outline"><?php _e( '&#x2193; Экспорт JSON', 'meal-menu' ); ?></a>
			</div>
			<div id="import-zone">
				<label class="btn btn-outline" style="cursor:pointer">
					&#x2191; <?php _e( 'Импорт JSON', 'meal-menu' ); ?>
					<input type="file" id="import-file" accept=".json,application/json" style="display:none">
				</label>
				<span id="import-filename" class="text-muted" style="margin-left:6px;font-size:.82rem"></span>
			</div>
		</div>
		<div id="import-msg" style="margin-top:8px"></div>
	</div>

	<hr style="margin-top:32px">

	<!-- Сброс данных -->
	<div class="panel" style="border-color:#e74c3c">
		<div class="panel-title" style="color:#e74c3c"><?php _e( 'Сброс данных плагина', 'meal-menu' ); ?></div>
		<p class="text-muted mb-2">
			<?php _e( 'Удаляет все таблицы плагина (календарь, шаблоны, блюда, пользователей, ОК, каникулы, отделения, настройки) и создаёт их заново с настройками по умолчанию. Файлы плагина не затрагиваются.', 'meal-menu' ); ?>
		</p>
		<button type="button" class="btn btn-danger" id="btn-reset"><?php _e( 'Сбросить все данные', 'meal-menu' ); ?></button>
	</div>
</div>

<script>
(function($) {
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';
	var btnMsg = document.getElementById('btn-msg');

	function apiPost(data, cb) {
		data.nonce = nonce;
		$.post(ajaxUrl + '?action=meal_save_settings', JSON.stringify(data), function(r) {
			try { cb(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { cb({ok:false, error: 'Ошибка сервера'}); }
		}).fail(function() { cb({ok:false, error: 'Сетевая ошибка'}); });
	}

	var defaultAiPrompt = <?php echo json_encode( \Meal_Menu\Importer_Photo::get_default_prompt() ); ?>;

	function showMsg(text, isError) {
		btnMsg.innerHTML = '<span style="color:' + (isError ? 'var(--error, #a02020)' : 'var(--success, #4a7a2a)') + '">' + text + '</span>';
		setTimeout(function() { btnMsg.innerHTML = ''; }, 4000);
	}

	document.getElementById('btn-save').addEventListener('click', function() {
		apiPost({
			action: 'save_settings',
			meal_food_dir: document.getElementById('meal_food_dir').value,
			meal_admin_email: document.getElementById('meal_admin_email').value,
			meal_mail_from: document.getElementById('meal_mail_from').value,
			meal_mail_from_name: document.getElementById('meal_mail_from_name').value,
			meal_smtp_host: document.getElementById('meal_smtp_host').value,
			meal_smtp_port: document.getElementById('meal_smtp_port').value,
			meal_smtp_user: document.getElementById('meal_smtp_user').value,
			meal_smtp_pass: document.getElementById('meal_smtp_pass').value,
			meal_smtp_secure: document.getElementById('meal_smtp_secure').value,
			meal_ai_prompt: document.getElementById('meal_ai_prompt').value
		}, function(r) {
			if (r.ok) {
				showMsg('Настройки сохранены', false);
			} else {
				showMsg(r.error || 'Ошибка сохранения', true);
			}
		});
	});

	// ─── AI prompt reset ──────────────────────────────────────────
	function showAiMsg(text, isError) {
		var el = document.getElementById('ai-prompt-msg');
		if (!el) return;
		el.innerHTML = '<span style="color:' + (isError ? 'var(--error, #a02020)' : 'var(--success, #4a7a2a)') + ';font-size:.82rem">' + text + '</span>';
		setTimeout(function() { el.innerHTML = ''; }, 4000);
	}

	document.getElementById('btn-ai-prompt-reset').addEventListener('click', function() {
		if (!confirm('<?php _e( 'Сбросить промт на значение по умолчанию?', 'meal-menu' ); ?>')) return;
		apiPost({action: 'reset_ai_prompt'}, function(r) {
			if (r.ok) {
				document.getElementById('meal_ai_prompt').value = r.prompt || defaultAiPrompt;
				showAiMsg('<?php _e( 'Промт сброшен. Нажмите «Сохранить настройки» чтобы применить.', 'meal-menu' ); ?>', false);
			} else {
				showAiMsg(r.error || '<?php _e( 'Ошибка', 'meal-menu' ); ?>', true);
			}
		});
	});

	// ─── Импорт ──────────────────────────────────────────────────
	document.getElementById('import-file').addEventListener('change', function() {
		var file = this.files[0];
		if (!file) return;
		document.getElementById('import-filename').textContent = file.name;
		var reader = new FileReader();
		reader.onload = function(e) {
			var data;
			try { data = JSON.parse(e.target.result); }
			catch(err) { document.getElementById('import-msg').innerHTML = '<div class="alert alert-error">Ошибка: неверный JSON</div>'; return; }
			if (!confirm('Импорт заменит все данные плагина. Продолжить?')) return;
			apiPost({action: 'import_data', payload: data}, function(r) {
				var el = document.getElementById('import-msg');
				if (r.ok) {
					el.innerHTML = '<div class="alert alert-success">Импорт выполнен. Страница перезагружается...</div>';
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					el.innerHTML = '<div class="alert alert-error">' + (r.error || 'Ошибка импорта') + '</div>';
				}
			});
		};
		reader.readAsText(file);
		this.value = '';
	});

	document.getElementById('btn-reset').addEventListener('click', function() {
		if (!confirm('<?php _e( 'Все данные плагина будут безвозвратно удалены. Продолжить?', 'meal-menu' ); ?>')) return;
		if (!confirm('<?php _e( 'Вы уверены? Отменить это действие невозможно.', 'meal-menu' ); ?>')) return;
		apiPost({action: 'reset_data'}, function(r) {
			if (r.ok) {
				showMsg('<?php _e( 'Данные сброшены. Страница перезагружается...', 'meal-menu' ); ?>', false);
				setTimeout(function() { location.reload(); }, 1500);
			} else {
				showMsg(r.error || 'Ошибка', true);
			}
		});
	});
})(jQuery);
</script>
