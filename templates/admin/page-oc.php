<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();
$oc = $db->get_oc_monitoring();

function oc_url_field( string $id, string $label, string $value, ?string $accept = null ): void {
	$is_local_file = $accept !== null && $value !== '' && str_contains( $value, '/oc/' );
	$fname         = $is_local_file ? basename( parse_url( $value, PHP_URL_PATH ) ) : '';
	$is_img        = $is_local_file && preg_match( '/\.(jpe?g|png|webp)$/i', $fname );
	?><div class="oc-field" style="margin-bottom:12px">
		<label for="<?php echo esc_attr( $id ); ?>" style="display:block;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:4px"><?php echo esc_html( $label ); ?></label>
		<?php if ( $accept !== null ): ?>
		<div style="display:flex;gap:6px">
			<input type="text" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://…" style="flex:1;padding:6px 10px;font-family:Georgia,serif;font-size:.9rem;border:1px solid var(--border-light);border-radius:var(--radius);color:var(--text)">
			<button type="button" class="btn btn-outline btn-sm btn-oc-upload" data-target="<?php echo esc_attr( $id ); ?>" data-accept="<?php echo esc_attr( $accept ); ?>">&#128206; <?php _e( 'Загрузить', 'meal-menu' ); ?></button>
		</div>
		<div id="badge_<?php echo esc_attr( $id ); ?>"<?php echo $fname ? '' : ' style="display:none"'; ?> style="margin-top:6px">
			<?php if ( $is_img ): ?>
				<a href="<?php echo esc_url( $value ); ?>" target="_blank"><img src="<?php echo esc_url( $value ); ?>" alt="<?php echo esc_attr( $fname ); ?>" style="max-width:150px;max-height:100px"></a>
			<?php elseif ( $fname ): ?>
				<a href="<?php echo esc_url( $value ); ?>" target="_blank">&#128196; <?php echo esc_html( $fname ); ?></a>
			<?php endif; ?>
		</div>
		<?php else: ?>
		<input type="text" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://…" style="width:100%;padding:6px 10px;font-family:Georgia,serif;font-size:.9rem;border:1px solid var(--border-light);border-radius:var(--radius);color:var(--text)">
		<?php endif; ?>
	</div><?php
}
?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Общественный контроль питания', 'meal-menu' ); ?></h1>
	<p class="text-muted mb-2"><?php _e( 'Заполните информацию для страницы общественного контроля питания. Форма соответствует требованиям ФИС ФРДО.', 'meal-menu' ); ?></p>
	<div id="oc-msg"></div>

	<form id="oc-form">
		<div class="panel">
			<div class="panel-title"><?php _e( 'Название организации и дата', 'meal-menu' ); ?></div>
			<div style="display:flex;gap:16px;flex-wrap:wrap">
				<div class="field" style="flex:2;min-width:200px">
					<label style="display:block;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:4px"><?php _e( 'Название', 'meal-menu' ); ?></label>
					<input type="text" id="school_name" value="<?php echo esc_attr( $oc['school_name'] ); ?>" style="width:100%;padding:6px 10px;font-family:Georgia,serif;font-size:.9rem;border:1px solid var(--border-light);border-radius:var(--radius);color:var(--text)">
				</div>
				<div class="field" style="flex:1;min-width:150px">
					<label style="display:block;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:4px"><?php _e( 'Дата отчёта', 'meal-menu' ); ?></label>
					<input type="date" id="report_date" value="<?php echo esc_attr( $oc['report_date'] ?? '' ); ?>" style="padding:6px 10px;font-family:Georgia,serif;font-size:.9rem;border:1px solid var(--border-light);border-radius:var(--radius);color:var(--text)">
				</div>
			</div>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 1. Положение и приказ о создании комиссии', 'meal-menu' ); ?></div>
			<?php oc_url_field( 's1_url', 'Ссылка на положение/приказ', $oc['s1_url'] ?? '', '.pdf' ); ?>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 2. Формы интерактивного взаимодействия', 'meal-menu' ); ?></div>
			<?php oc_url_field( 's2_hotline', 'Телефон горячей линии', $oc['s2_hotline'] ?? '' ); ?>
			<?php oc_url_field( 's2_chat_url', 'Ссылка на чат', $oc['s2_chat_url'] ?? '' ); ?>
			<?php oc_url_field( 's2_forum_url', 'Ссылка на форум/обратную связь', $oc['s2_forum_url'] ?? '' ); ?>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 3. Лечебные/диетические меню', 'meal-menu' ); ?></div>
			<?php for ( $i = 1; $i <= 4; $i++ ): ?>
			<div style="display:flex;gap:12px;margin-bottom:12px">
				<div style="flex:1">
					<?php oc_url_field( "s3_diet{$i}_type", sprintf( __( 'Тип диеты %d', 'meal-menu' ), $i ), $oc[ "s3_diet{$i}_type" ] ?? '' ); ?>
				</div>
				<div style="flex:2">
					<?php oc_url_field( "s3_diet{$i}_url", sprintf( __( 'Ссылка %d', 'meal-menu' ), $i ), $oc[ "s3_diet{$i}_url" ] ?? '', '.pdf' ); ?>
				</div>
			</div>
			<?php endfor; ?>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 4. Анкетирование', 'meal-menu' ); ?></div>
			<?php oc_url_field( 's4_survey_url', 'Ссылка на анкету', $oc['s4_survey_url'] ?? '' ); ?>
			<?php oc_url_field( 's4_results_url', 'Ссылка на результаты', $oc['s4_results_url'] ?? '', '.pdf' ); ?>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 5. Информация о здоровом питании', 'meal-menu' ); ?></div>
			<?php oc_url_field( 's5_page_url', 'Ссылка на страницу о здоровом питании', $oc['s5_page_url'] ?? '' ); ?>
			<?php oc_url_field( 's5_materials_url', 'Ссылка на материалы', $oc['s5_materials_url'] ?? '', '.pdf' ); ?>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 6. Результаты контрольных мероприятий', 'meal-menu' ); ?></div>
			<?php oc_url_field( 's6_acts_url', 'Ссылка на акты', $oc['s6_acts_url'] ?? '', '.pdf' ); ?>
			<?php oc_url_field( 's6_photos_url', 'Ссылка на фото', $oc['s6_photos_url'] ?? '', 'image/*' ); ?>
		</div>

		<div class="panel" style="border-left:4px solid var(--orange)">
			<div class="panel-title"><?php _e( 'Раздел 7. Оценка пищевых отходов', 'meal-menu' ); ?></div>
			<div class="field" style="max-width:300px">
				<label style="display:block;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:4px"><?php _e( 'Уровень отходов', 'meal-menu' ); ?></label>
				<select id="s7_waste_level" style="width:100%;padding:6px 10px;font-family:Georgia,serif;font-size:.9rem;border:1px solid var(--border-light);border-radius:var(--radius);color:var(--text)">
					<option value="none"<?php selected( $oc['s7_waste_level'], 'none' ); ?>><?php _e( 'Не выбран', 'meal-menu' ); ?></option>
					<option value="20"<?php selected( $oc['s7_waste_level'], '20' ); ?>>20%</option>
					<option value="30"<?php selected( $oc['s7_waste_level'], '30' ); ?>>30%</option>
					<option value="40"<?php selected( $oc['s7_waste_level'], '40' ); ?>>40%</option>
					<option value="50"<?php selected( $oc['s7_waste_level'], '50' ); ?>>50%</option>
				</select>
			</div>
		</div>

		<div style="text-align:right">
			<button type="submit" class="btn btn-primary"><?php _e( 'Сохранить', 'meal-menu' ); ?></button>
		</div>
	</form>
</div>

<script>
(function($) {
	var msgEl = document.getElementById('oc-msg');
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';

	document.getElementById('oc-form').addEventListener('submit', function(e) {
		e.preventDefault();
		var data = {
			action: 'meal_save_oc', nonce: nonce,
			school_name: document.getElementById('school_name').value,
			report_date: document.getElementById('report_date').value
		};
		['s1_url','s2_hotline','s2_chat_url','s2_forum_url',
		 's3_diet1_type','s3_diet1_url','s3_diet2_type','s3_diet2_url',
		 's3_diet3_type','s3_diet3_url','s3_diet4_type','s3_diet4_url',
		 's4_survey_url','s4_results_url','s5_page_url','s5_materials_url',
		 's6_acts_url','s6_photos_url','s7_waste_level'
		].forEach(function(id) {
			var el = document.getElementById(id);
			if (el) data[id] = el.value;
		});
		$.post(ajaxUrl, data, function(r) {
			if (r.ok) {
				msgEl.innerHTML = '<div class="alert alert-success"><?php _e( 'Сохранено.', 'meal-menu' ); ?></div>';
				setTimeout(function() { msgEl.innerHTML = ''; }, 3000);
			} else {
				msgEl.innerHTML = '<div class="alert alert-error">' + (r.error || 'Ошибка') + '</div>';
			}
		}, 'json');
	});

	// Upload
	document.querySelectorAll('.btn-oc-upload').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var input = document.createElement('input');
			input.type = 'file';
			input.accept = btn.dataset.accept || '.pdf,image/*';
			input.onchange = function() {
				if (!input.files[0]) return;
				var fd = new FormData();
				fd.append('action', 'meal_upload_oc');
				fd.append('nonce', nonce);
				fd.append('file', input.files[0]);
				var targetId = btn.dataset.target;
				$.ajax({
					url: ajaxUrl,
					type: 'POST',
					data: fd,
					processData: false,
					contentType: false,
					success: function(r) {
						if (r.ok) {
							document.getElementById(targetId).value = r.url;
							var badge = document.getElementById('badge_' + targetId);
							if (badge) {
								var isImg = r.url.match(/\.(jpe?g|png|webp)$/i);
								if (isImg) {
									badge.innerHTML = '<a href="' + r.url + '" target="_blank"><img src="' + r.url + '" style="max-width:150px;max-height:100px"></a>';
								} else {
									badge.innerHTML = '<a href="' + r.url + '" target="_blank">&#128196; ' + r.filename + '</a>';
								}
								badge.style.display = '';
							}
						} else {
							alert(r.error || 'Ошибка загрузки');
						}
					}
				});
			};
			input.click();
		});
	});
})(jQuery);
</script>
