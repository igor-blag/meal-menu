<?php if ( ! defined( 'ABSPATH' ) ) exit; $db = \Meal_Menu\DB::instance(); $org_name = $db->get_org_name(); ?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Справка', 'meal-menu' ); ?></h1>

	<div style="margin-bottom:36px">
		<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--orange);padding-left:12px;margin-bottom:14px"><?php _e( '1. Шаблоны циклов', 'meal-menu' ); ?></h2>
		<p style="margin-bottom:8px;line-height:1.6"><?php _e( 'Шаблон цикла — это N-дневный набор меню, который повторяется в течение учебного года. Каждый день шаблона содержит блюда по типам приёма пищи:', 'meal-menu' ); ?></p>
		<ul style="padding-left:22px">
			<li><?php _e( 'Завтрак — горячее блюдо, напиток, хлеб', 'meal-menu' ); ?></li>
			<li><?php _e( 'Завтрак 2 — фрукты', 'meal-menu' ); ?></li>
			<li><?php _e( 'Обед — закуска, первое, второе, гарнир', 'meal-menu' ); ?></li>
			<li><?php _e( 'Полдник, Ужин, Ужин 2 — для интернатных отделений', 'meal-menu' ); ?></li>
		</ul>
	</div>

	<div style="margin-bottom:36px">
		<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--orange);padding-left:12px;margin-bottom:14px"><?php _e( '2. Календарь', 'meal-menu' ); ?></h2>
		<p style="margin-bottom:8px;line-height:1.6"><?php _e( 'В календаре назначаются шаблоны на конкретные даты. Клик по ячейке дня открывает меню:', 'meal-menu' ); ?></p>
		<ul style="padding-left:22px">
			<li><?php _e( 'Выбрать номер дня цикла — назначить шаблон', 'meal-menu' ); ?></li>
			<li><?php _e( '«Выходной» — пометить день как нерабочий (без питания)', 'meal-menu' ); ?></li>
			<li><?php _e( '«Применить цикл» — автоматически заполнить рабочие дни циклами', 'meal-menu' ); ?></li>
		</ul>
		<p style="margin-bottom:8px;line-height:1.6"><?php _e( 'Рабочие дни, выходные и каникулы настраиваются в разделе «Настройки».', 'meal-menu' ); ?></p>
	</div>

	<div style="margin-bottom:36px">
		<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--orange);padding-left:12px;margin-bottom:14px"><?php _e( '3. Excel-файлы', 'meal-menu' ); ?></h2>
		<p style="margin-bottom:8px;line-height:1.6"><?php _e( 'Система автоматически генерирует Excel-файлы для мониторингового бота ФЦМПО:', 'meal-menu' ); ?></p>
		<ul style="padding-left:22px">
			<li><strong>Ежедневные</strong> — YYYY-MM-DD[-suffix].xlsx (при сохранении дня и при создании всех файлов месяца)</li>
			<li><strong>Типовое меню</strong> — tmYYYY-type.xlsx (при сохранении sm-шаблона)</li>
			<li><strong>Годовой план-график</strong> — kpYYYY-type.xlsx (по запросу)</li>
		</ul>
	</div>

	<div style="margin-bottom:36px">
		<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--orange);padding-left:12px;margin-bottom:14px"><?php _e( '4. Настройки', 'meal-menu' ); ?></h2>
		<p style="margin-bottom:8px;line-height:1.6"><?php _e( 'В настройках задаются:', 'meal-menu' ); ?></p>
		<ul style="padding-left:22px">
			<li><?php _e( 'Название организации', 'meal-menu' ); ?></li>
			<li><?php _e( 'Отделения (школы/корпуса) с параметрами: рабочие дни, длина цикла, постфикс файлов', 'meal-menu' ); ?></li>
			<li><?php _e( 'Учебный год и каникулы', 'meal-menu' ); ?></li>
			<li><?php _e( 'Должность и ФИО утверждающего лица (для шапки tm-файла)', 'meal-menu' ); ?></li>
		</ul>
	</div>
</div>
