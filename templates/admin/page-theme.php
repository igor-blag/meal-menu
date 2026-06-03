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
			<div class="palette-card" data-key="<?php echo esc_attr( $key ); ?>" style="cursor:pointer;border:2px solid <?php echo $key === $cur_palette ? 'var(--orange)' : 'var(--border-light)'; ?>;padding:12px;background:#fff;border-radius:4px;transition:border-color .15s">
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
				<div style="font-size:.78rem;color:var(--muted)"><?php echo esc_html( $p['desc'] ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="panel">
		<div class="panel-title"><?php _e( 'Стиль отображения', 'meal-menu' ); ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px" id="layout-list">
			<?php foreach ( $layouts as $key => $l ): ?>
			<div class="layout-card" data-key="<?php echo esc_attr( $key ); ?>" style="cursor:pointer;border:2px solid <?php echo $key === $cur_layout ? 'var(--orange)' : 'var(--border-light)'; ?>;padding:12px;background:#fff;border-radius:4px;transition:border-color .15s">
				<div style="font-weight:bold;font-size:.9rem"><?php echo esc_html( $l['label'] ); ?></div>
				<div style="font-size:.78rem;color:var(--muted)"><?php echo esc_html( $l['desc'] ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="panel">
		<div class="panel-title"><?php _e( 'Предпросмотр', 'meal-menu' ); ?></div>
		<div id="theme-preview" class="meal-wrapper palette-<?php echo esc_attr( $cur_palette ); ?> layout-<?php echo esc_attr( $cur_layout ); ?>" style="max-width:100%;overflow:auto;border:1px solid var(--border-light)">
			<header class="meal-header">
				<div class="meal-logo"><?php _e( 'Школа', 'meal-menu' ); ?></div>
			</header>
			<div class="meal-container" style="padding:16px">
				<div class="meal-title" style="font-size:1.1rem"><?php _e( 'Пример календаря', 'meal-menu' ); ?></div>
				<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:var(--meal-grid-gap);background:var(--meal-border);border:2px solid var(--meal-grid-border)">
					<?php foreach ( array( 'Пн','Вт','Ср','Чт','Пт','Сб','Вс' ) as $h ): ?>
					<div class="meal-cal-head"><?php echo $h; ?></div>
					<?php endforeach; ?>
					<div class="meal-cal-cell has-menu"><div class="meal-cal-day">1</div><a class="meal-cal-link">Меню</a></div>
					<div class="meal-cal-cell"><div class="meal-cal-day">2</div></div>
					<div class="meal-cal-cell holiday"><div class="meal-cal-day">3</div><div class="meal-holiday-label">Вых.</div></div>
					<div class="meal-cal-cell has-menu"><div class="meal-cal-day">4</div><a class="meal-cal-link">Меню</a></div>
					<div class="meal-cal-cell"><div class="meal-cal-day">5</div></div>
					<div class="meal-cal-cell weekend"><div class="meal-cal-day">6</div></div>
					<div class="meal-cal-cell weekend"><div class="meal-cal-day">7</div></div>
				</div>
				<div style="margin-top:12px">
					<div class="meal-block-title" style="font-size:.8rem"><?php _e( 'Завтрак', 'meal-menu' ); ?></div>
					<table class="meal-table">
						<thead><tr><th style="width:50%"><?php _e( 'Блюдо', 'meal-menu' ); ?></th><th style="width:15%"><?php _e( 'Выход, г', 'meal-menu' ); ?></th><th style="width:15%"><?php _e( 'Ккал', 'meal-menu' ); ?></th></tr></thead>
						<tbody><tr><td><?php _e( 'Каша овсяная', 'meal-menu' ); ?></td><td class="num">200,0</td><td class="num">150,0</td></tr></tbody>
					</table>
				</div>
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
		document.querySelectorAll('.palette-card').forEach(function(c) { c.style.borderColor = 'var(--border-light)'; });
		card.style.borderColor = 'var(--orange)';
		updatePreview();
	});

	document.getElementById('layout-list').addEventListener('click', function(e) {
		var card = e.target.closest('.layout-card');
		if (!card) return;
		curLayout = card.dataset.key;
		document.querySelectorAll('.layout-card').forEach(function(c) { c.style.borderColor = 'var(--border-light)'; });
		card.style.borderColor = 'var(--orange)';
		updatePreview();
	});

	function updatePreview() {
		preview.className = 'meal-wrapper palette-' + curPalette + ' layout-' + curLayout;
		preview.style.maxWidth = '100%';
		preview.style.overflow = 'auto';
		preview.style.border = '1px solid var(--border-light)';
	}

	document.getElementById('btn-save-theme').addEventListener('click', function() {
		var msgEl = document.getElementById('meal-theme-msg');
		$.post(ajaxUrl + '?action=meal_save_theme', JSON.stringify({ nonce: nonce, palette: curPalette, layout: curLayout }), function(r) {
			if (r.ok) {
				msgEl.innerHTML = '<div class="alert alert-success"><?php _e( 'Сохранено.', 'meal-menu' ); ?></div>';
				setTimeout(function() { msgEl.innerHTML = ''; }, 3000);
			} else {
				msgEl.innerHTML = '<div class="alert alert-error">' + (r.error || 'Ошибка') + '</div>';
			}
		});
	});
})(jQuery);
</script>
