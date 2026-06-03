<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();

$enabled_depts = $db->get_enabled_departments();
$org_name      = $db->get_org_name();
$oc            = $db->get_oc_monitoring();

$waste_labels = array( 'none' => '', '20' => '&#60;&#160;20%', '30' => '20–30%', '40' => '30–40%', '50' => '&#62;&#160;50%' );

$palette = get_option( 'meal_theme_palette', 'retro' );
$layout  = get_option( 'meal_theme_layout', 'classic' );
?>
<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>">
	<header class="meal-header">
		<div class="meal-logo"><?php echo esc_html( $org_name ?: __( 'Организация питания', 'meal-menu' ) ); ?></div>
		<nav class="meal-nav">
			<a class="meal-nav-link" href="<?php echo esc_url( remove_query_arg( 'page' ) ); ?>"><?php _e( 'Календарь', 'meal-menu' ); ?></a>
			<a class="meal-nav-link" href="<?php echo esc_url( add_query_arg( 'page', 'meal_menu' ) ); ?>"><?php _e( 'Типовое меню', 'meal-menu' ); ?></a>
		</nav>
	</header>

	<div class="meal-container">
		<div class="meal-title"><?php _e( 'Общественный контроль питания', 'meal-menu' ); ?></div>

		<?php if ( $oc['school_name'] ): ?>
		<p style="margin-bottom:16px;font-size:.9rem;color:var(--meal-muted)"><?php echo esc_html( $oc['school_name'] ); ?></p>
		<?php endif; ?>

		<?php foreach ( array(
			's1_url'      => array( 'title' => 'Раздел 1. Положение и приказ о создании комиссии', 'type' => 'url' ),
			's2_hotline'  => array( 'title' => 'Раздел 2. Формы интерактивного взаимодействия', 'type' => 'section2' ),
			's3_diet1_type' => array( 'title' => 'Раздел 3. Лечебные/диетические меню', 'type' => 'section3' ),
			's4_survey_url' => array( 'title' => 'Раздел 4. Анкетирование', 'type' => 'section4' ),
			's5_page_url'   => array( 'title' => 'Раздел 5. Информация о здоровом питании', 'type' => 'section5' ),
			's6_acts_url'   => array( 'title' => 'Раздел 6. Результаты контрольных мероприятий', 'type' => 'section6' ),
			's7_waste_level'=> array( 'title' => 'Раздел 7. Оценка пищевых отходов', 'type' => 'section7' ),
		) as $key => $section ):
			$show = false;
			switch ( $section['type'] ) {
				case 'url':
					$show = ! empty( $oc[ $key ] );
					break;
				case 'section2':
					$show = ! empty( $oc['s2_hotline'] ) || ! empty( $oc['s2_chat_url'] ) || ! empty( $oc['s2_forum_url'] );
					break;
				case 'section3':
					for ( $i = 1; $i <= 4; $i++ ) {
						if ( ! empty( $oc[ "s3_diet{$i}_type" ] ) || ! empty( $oc[ "s3_diet{$i}_url" ] ) ) { $show = true; break; }
					}
					break;
				case 'section4':
					$show = ! empty( $oc['s4_survey_url'] ) || ! empty( $oc['s4_results_url'] );
					break;
				case 'section5':
					$show = ! empty( $oc['s5_page_url'] ) || ! empty( $oc['s5_materials_url'] );
					break;
				case 'section6':
					$show = ! empty( $oc['s6_acts_url'] ) || ! empty( $oc['s6_photos_url'] );
					break;
				case 'section7':
					$show = ! empty( $oc['s7_waste_level'] ) && $oc['s7_waste_level'] !== 'none';
					break;
			}
			if ( ! $show ) continue;
		?>
		<div class="meal-block" style="border-left:4px solid var(--meal-primary);padding-left:20px">
			<div class="meal-block-title" style="background:transparent;color:var(--meal-heading);padding-left:0"><?php echo esc_html( $section['title'] ); ?></div>
			<?php
			if ( $section['type'] === 'url' ): ?>
				<a href="<?php echo esc_url( $oc[ $key ] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Перейти к документу', 'meal-menu' ); ?></a>
			<?php elseif ( $section['type'] === 'section2' ): ?>
				<?php if ( $oc['s2_hotline'] ): ?><p style="margin-bottom:4px"><strong><?php _e( 'Горячая линия:', 'meal-menu' ); ?></strong> <?php echo esc_html( $oc['s2_hotline'] ); ?></p><?php endif; ?>
				<?php if ( $oc['s2_chat_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s2_chat_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Чат для обратной связи', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php if ( $oc['s2_forum_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s2_forum_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Форум / обратная связь', 'meal-menu' ); ?></a></p><?php endif; ?>
			<?php elseif ( $section['type'] === 'section3' ): ?>
				<?php for ( $i = 1; $i <= 4; $i++ ):
					$t = $oc[ "s3_diet{$i}_type" ] ?? '';
					$u = $oc[ "s3_diet{$i}_url" ] ?? '';
					if ( ! $t && ! $u ) continue;
				?><p style="margin-bottom:4px"><?php echo esc_html( $t ); ?>: <?php if ( $u ): ?><a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'скачать', 'meal-menu' ); ?></a><?php endif; ?></p>
				<?php endfor; ?>
			<?php elseif ( $section['type'] === 'section4' ): ?>
				<?php if ( $oc['s4_survey_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s4_survey_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Пройти анкету', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php if ( $oc['s4_results_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s4_results_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Результаты анкетирования', 'meal-menu' ); ?></a></p><?php endif; ?>
			<?php elseif ( $section['type'] === 'section5' ): ?>
				<?php if ( $oc['s5_page_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s5_page_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Страница о здоровом питании', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php if ( $oc['s5_materials_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s5_materials_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Материалы', 'meal-menu' ); ?></a></p><?php endif; ?>
			<?php elseif ( $section['type'] === 'section6' ): ?>
				<?php if ( $oc['s6_acts_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s6_acts_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Акты контроля', 'meal-menu' ); ?></a></p><?php endif; ?>
				<?php if ( $oc['s6_photos_url'] ): ?><p style="margin-bottom:4px"><a href="<?php echo esc_url( $oc['s6_photos_url'] ); ?>" target="_blank" rel="noopener" style="color:var(--meal-primary)"><?php _e( 'Фотоматериалы', 'meal-menu' ); ?></a></p><?php endif; ?>
			<?php elseif ( $section['type'] === 'section7' ): ?>
				<p><?php _e( 'Уровень пищевых отходов:', 'meal-menu' ); ?> <?php echo $waste_labels[ $oc['s7_waste_level'] ] ?? ''; ?></p>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>

	<footer class="meal-footer">
		<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">github.com/igor-blag/web-food</a>
	</footer>
</div>
