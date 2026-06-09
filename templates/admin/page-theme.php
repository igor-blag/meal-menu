<?php if ( ! defined( 'ABSPATH' ) ) exit;
$palettes = array(
	'retro'       => array( 'label' => 'Ретро',       'desc' => 'Оранжево-чёрная, школьный стиль' ),
	'futuristic'  => array( 'label' => 'Футуризм',    'desc' => 'Циан на тёмном, неон' ),
	'minimal'     => array( 'label' => 'Минимализм',  'desc' => 'Монохром, чисто, строго' ),
	'nature'      => array( 'label' => 'Природа',     'desc' => 'Зелёный, земля, спокойствие' ),
	'school'      => array( 'label' => 'Академический','desc' => 'Синий, классический учебный' ),
	'warm'        => array( 'label' => 'Тёплый',      'desc' => 'Бордовый, золото, уют' ),
	'ocean'       => array( 'label' => 'Океан',       'desc' => 'Бирюза, песок, свежесть' ),
);
$layouts = array(
	'classic' => array( 'label' => 'Классический', 'desc' => 'Source Serif 4, плотная сетка, острые углы' ),
	'modern'  => array( 'label' => 'Современный',  'desc' => 'System sans-serif, скругления, отступы' ),
	'air'     => array( 'label' => 'Воздушный',    'desc' => 'Без рамок, много воздуха, тонко' ),
);
$cur_palette = get_option( 'meal_theme_palette', 'retro' );
$cur_layout  = get_option( 'meal_theme_layout', 'classic' );
?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Оформление публичных страниц', 'meal-menu' ); ?></h1>
	<p class="text-muted mb-2"><?php _e( 'Выберите цветовую палитру и стиль отображения календаря и меню для родителей.', 'meal-menu' ); ?></p>

	<div id="meal-theme-msg"></div>

	<div class="panel">
		<div class="panel-title"><?php _e( 'Цветовая палитра', 'meal-menu' ); ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px" id="palette-list">
			<?php foreach ( $palettes as $key => $p ): ?>
			<div class="palette-card" data-key="<?php echo esc_attr( $key ); ?>" style="cursor:pointer;border:2px solid <?php echo $key === $cur_palette ? 'var(--wp-blue)' : 'var(--wp-border-subtle)'; ?>;padding:12px;background:#fff;border-radius:4px;transition:border-color .15s">
				<div style="display:flex;gap:4px;margin-bottom:8px">
					<?php
					$swatches = match( $key ) {
						'retro'       => array( '#c8580a', '#1a1100', '#fdf4e3' ),
						'futuristic'  => array( '#00d4ff', '#0a0a1a', '#1a1a35' ),
						'minimal'     => array( '#333333', '#ffffff', '#f8f8f8' ),
						'nature'      => array( '#4a7a2a', '#2a3a1a', '#f5f8f0' ),
						'school'      => array( '#1a56db', '#0a1a3a', '#f4f6fa' ),
						'warm'        => array( '#8a2020', '#2a0a00', '#faf3ea' ),
						'ocean'       => array( '#0a7a7a', '#0a2a28', '#f0f8f5' ),
					};
					foreach ( $swatches as $s ): ?>
					<div style="width:24px;height:24px;background:<?php echo $s; ?>;border-radius:3px;border:1px solid #ddd"></div>
					<?php endforeach; ?>
				</div>
				<div style="font-weight:bold;font-size:.9rem"><?php echo esc_html( $p['label'] ); ?></div>
				<div style="font-size:.78rem;color:var(--wp-muted)"><?php echo esc_html( $p['desc'] ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="panel">
		<div class="panel-title"><?php _e( 'Стиль отображения', 'meal-menu' ); ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px" id="layout-list">
			<?php foreach ( $layouts as $key => $l ): ?>
			<div class="layout-card" data-key="<?php echo esc_attr( $key ); ?>" style="cursor:pointer;border:2px solid <?php echo $key === $cur_layout ? 'var(--wp-blue)' : 'var(--wp-border-subtle)'; ?>;padding:12px;background:#fff;border-radius:4px;transition:border-color .15s">
				<div style="font-weight:bold;font-size:.9rem"><?php echo esc_html( $l['label'] ); ?></div>
				<div style="font-size:.78rem;color:var(--wp-muted)"><?php echo esc_html( $l['desc'] ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="panel">
		<div class="panel-title"><?php _e( 'Предпросмотр', 'meal-menu' ); ?></div>
		<?php
		$preview_days = array(
			array( 1, 'has-menu' ), array( 2, '' ), array( 3, 'holiday' ), array( 4, 'has-menu' ),
			array( 5, '' ), array( 6, 'weekend' ), array( 7, 'weekend' ),
			array( 8, 'has-menu' ), array( 9, 'vacation' ), array( 10, 'holiday' ), array( 11, 'has-menu' ),
			array( 12, '' ), array( 13, 'weekend' ), array( 14, 'weekend' ),
			array( 15, 'has-menu today' ), array( 16, '' ), array( 17, 'holiday' ), array( 18, 'has-menu' ),
			array( 19, '' ), array( 20, 'weekend' ), array( 21, 'weekend' ),
			array( 22, 'has-menu' ), array( 23, '' ), array( 24, 'holiday' ), array( 25, 'has-menu' ),
			array( 26, '' ), array( 27, 'weekend' ), array( 28, 'weekend' ),
			array( 29, 'has-menu' ), array( 30, '' ), array( 31, 'holiday' ),
		);
		$preview_meals = array(
			'Завтрак' => array(
				array( 'Каша овсяная', '200,0', '150,0' ),
				array( 'Бутерброд с маслом', '50,0', '120,0' ),
				array( 'Чай сладкий', '200,0', '30,0' ),
			),
			'Обед' => array(
				array( 'Борщ', '250,0', '180,0' ),
				array( 'Котлета куриная', '80,0', '210,0' ),
				array( 'Пюре картофельное', '150,0', '130,0' ),
				array( 'Компот', '200,0', '60,0' ),
			),
			'Полдник' => array(
				array( 'Булочка с повидлом', '80,0', '220,0' ),
				array( 'Кефир', '200,0', '100,0' ),
			),
		);
		?>
		<div id="theme-preview" class="meal-wrapper palette-<?php echo esc_attr( $cur_palette ); ?> layout-<?php echo esc_attr( $cur_layout ); ?>" style="max-width:100%;overflow:auto;border:1px solid var(--wp-border-subtle)">
			<header class="meal-header">
				<div class="meal-logo"><?php _e( 'Школа', 'meal-menu' ); ?></div>
			</header>
			<div class="meal-container">
				<div class="meal-title"><?php _e( 'Календарь питания — Июнь 2026', 'meal-menu' ); ?></div>
				<div class="meal-cal-grid">
					<?php foreach ( array( 'Пн','Вт','Ср','Чт','Пт','Сб','Вс' ) as $h ): ?>
					<div class="meal-cal-head"><?php echo $h; ?></div>
					<?php endforeach; ?>
					<?php foreach ( $preview_days as $i => $d ):
						$day_num = $d[0];
						$classes = 'meal-cal-cell' . ( $d[1] ? ' ' . $d[1] : '' );
					?>
					<div class="<?php echo $classes; ?>">
						<div class="meal-cal-day"><?php echo $day_num; ?></div>
						<?php if ( str_contains( $d[1], 'has-menu' ) ): ?>
						<a class="meal-cal-link">Меню</a>
						<?php elseif ( str_contains( $d[1], 'holiday' ) ): ?>
						<div class="meal-holiday-label">Вых.</div>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="meal-legend">
					<div class="meal-legend-item"><div class="meal-legend-dot has-menu"></div> Меню есть</div>
					<div class="meal-legend-item"><div class="meal-legend-dot holiday"></div> Выходной</div>
					<div class="meal-legend-item"><div class="meal-legend-dot vacation"></div> Каникулы</div>
					<div class="meal-legend-item"><div class="meal-legend-dot no-menu"></div> Нет меню</div>
				</div>
				<?php foreach ( $preview_meals as $title => $items ): ?>
				<div class="meal-block">
					<div class="meal-block-title"><?php echo esc_html( $title ); ?></div>
					<table class="meal-table">
						<thead><tr><th style="width:50%"><?php _e( 'Блюдо', 'meal-menu' ); ?></th><th style="width:15%"><?php _e( 'Выход, г', 'meal-menu' ); ?></th><th style="width:15%"><?php _e( 'Ккал', 'meal-menu' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $items as $row ): ?>
							<tr><td><?php echo esc_html( $row[0] ); ?></td><td class="num"><?php echo esc_html( $row[1] ); ?></td><td class="num"><?php echo esc_html( $row[2] ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<div style="text-align:right">
		<button type="button" class="btn btn-primary" id="btn-save-theme"><?php _e( 'Сохранить', 'meal-menu' ); ?></button>
	</div>
</div>

<script>
(function($) {
	var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var nonce   = '<?php echo wp_create_nonce( 'meal_menu_nonce' ); ?>';
	var curPalette = '<?php echo esc_js( $cur_palette ); ?>';
	var curLayout  = '<?php echo esc_js( $cur_layout ); ?>';
	var preview = document.getElementById('theme-preview');

	document.getElementById('palette-list').addEventListener('click', function(e) {
		var card = e.target.closest('.palette-card');
		if (!card) return;
		curPalette = card.dataset.key;
		document.querySelectorAll('.palette-card').forEach(function(c) { c.style.borderColor = 'var(--wp-border-subtle)'; });
		card.style.borderColor = 'var(--wp-blue)';
		updatePreview();
	});

	document.getElementById('layout-list').addEventListener('click', function(e) {
		var card = e.target.closest('.layout-card');
		if (!card) return;
		curLayout = card.dataset.key;
		document.querySelectorAll('.layout-card').forEach(function(c) { c.style.borderColor = 'var(--wp-border-subtle)'; });
		card.style.borderColor = 'var(--wp-blue)';
		updatePreview();
	});

	function updatePreview() {
		preview.className = 'meal-wrapper palette-' + curPalette + ' layout-' + curLayout;
		preview.style.maxWidth = '100%';
		preview.style.overflow = 'auto';
		preview.style.border = '1px solid var(--wp-border-subtle)';
	}

	document.getElementById('btn-save-theme').addEventListener('click', function() {
		var msgEl = document.getElementById('meal-theme-msg');
		$.ajax({
			url: ajaxUrl + '?action=meal_save_theme',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({ nonce: nonce, palette: curPalette, layout: curLayout }),
			success: function(r) {
				if (r.ok) {
					msgEl.innerHTML = '<div class="alert alert-success"><?php _e( 'Сохранено.', 'meal-menu' ); ?></div>';
					setTimeout(function() { msgEl.innerHTML = ''; }, 3000);
				} else {
					msgEl.innerHTML = '<div class="alert alert-error">' + (r.error || 'Ошибка') + '</div>';
				}
			}
		});
	});
})(jQuery);
</script>
