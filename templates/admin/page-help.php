<?php if ( ! defined( 'ABSPATH' ) ) exit; $db = \Meal_Menu\DB::instance(); $org_name = $db->get_org_name(); ?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Справка', 'meal-menu' ); ?></h1>

	<div style="margin-bottom:36px;background:var(--bg-note,#fef6ee);border-left:4px solid var(--orange);padding:16px 20px;line-height:1.6">
		<p style="margin:0"><?php _e( 'Плагин «Меню питания» разработан специально для мониторинга питания <strong>ФЦМПО</strong> (Федеральный центр мониторинга питания обучающихся).', 'meal-menu' ); ?></p>
		<p style="margin:8px 0 0"><?php _e( 'Основная польза — автоматизированная публикация всех необходимых для мониторинга файлов <strong>.xlsx</strong> (ежедневное меню, типовое меню, годовой план-график). Дополнительно плагин красиво выводит содержимое меню для посетителей сайта.', 'meal-menu' ); ?></p>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--orange);padding-left:12px;margin-bottom:16px"><?php _e( 'Если плагин только что установлен', 'meal-menu' ); ?></h2>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div>
			<strong><?php _e( 'Настройки пищеблока', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Заполните информацию о пищеблоке и данные о графике работы: выходные дни, каникулы, учебный год. Для каждого отделения можно задать длину цикла и включить/отключить публикацию xlsx-файлов.', 'meal-menu' ); ?></p>
			<p style="margin:4px 0 0;line-height:1.5;font-size:.85rem;color:var(--muted)"><?php _e( 'Если у нескольких отделений одинаковая длина цикла и одинаковое меню — выберите в настройках «Объединить календарь с [отделение]». Тогда календарь будет общий, а xlsx-файлы будут генерироваться для каждого отделения со своим постфиксом.', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div>
			<strong><?php _e( 'Настройки плагина', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Укажите папку, в которую будут публиковаться файлы .xlsx для мониторинга.', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div>
			<strong><?php _e( 'Общественный контроль', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Заполните разделы общественного контроля питания (ФИС ФРДО): приказ о комиссии, формы обратной связи, анкетирование, акты проверок и т.д.', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div>
			<strong><?php _e( 'Шаблоны циклов', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Создайте наборы блюд по дням цикла. Это можно сделать вручную через редактор или импортировать из файла .xlsx. Шаблоны заполняются один раз и меняются редко (например, при смене утверждённого меню).', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:24px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">5</div>
		<div>
			<strong><?php _e( 'Календарь — заполнять каждый месяц', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Ежемесячно назначайте дни цикла на конкретные даты. Именно из календаря генерируются файлы для мониторинга.', 'meal-menu' ); ?></p>
		</div>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--orange);padding-left:12px;margin-bottom:16px"><?php _e( 'Работа с календарём (каждый месяц)', 'meal-menu' ); ?></h2>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div style="line-height:1.5"><?php _e( 'Выберите <strong>вкладку отделения питания</strong>, которое будете заполнять. Если одно отделение объединено с другим — во вкладке будет указано оба названия.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div style="line-height:1.5"><?php _e( 'Перейдите на нужный месяц стрелками. Серые ячейки до и после сетки показывают данные соседних месяцев для контекста.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div style="line-height:1.5"><?php _e( 'При необходимости отметьте <strong>выходные</strong> и <strong>рабочие дни</strong> кликом по ячейке. День, отмеченный рабочим, отображается на белом фоне с пометкой «Рабочий день».', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div style="line-height:1.5"><?php _e( 'Кликните на <strong>первый рабочий день</strong>, укажите <strong>номер дня меню</strong> (от какого числа цикла отталкиваться) и нажмите «Заполнить до конца месяца».', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">5</div>
		<div style="line-height:1.5"><?php _e( 'Когда все дни месяца назначены — нажмите <strong>«Создать файлы»</strong>. Будут сгенерированы файлы ежедневного меню для этого и всех объединённых отделений.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start">
		<div style="background:var(--orange);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">6</div>
		<div style="line-height:1.5"><?php _e( 'Повторите процедуру для <strong>других отделений</strong> — переключитесь на их вкладку и выполните шаги 2–5. Если у другого отделения такой же цикл и оно уже заполнено — в боковой панели появится кнопка «Копировать из [название]».', 'meal-menu' ); ?></div>
	</div>

	<div style="margin-top:20px;padding:12px 16px;background:var(--bg-note,#fef6ee);border-left:3px solid var(--orange);line-height:1.6">
		<strong><?php _e( 'Подсказки:', 'meal-menu' ); ?></strong>
		<ul style="margin:4px 0 0;padding-left:18px">
			<li><?php _e( 'Если в середине месяца график поменялся — кликните на любой день, измените номер меню и нажмите «Заполнить до конца месяца»: остаток пересчитается автоматически.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Кнопка «Заполнить [месяц]» заполняет только пустые дни, не меняя уже назначенные.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Если у отделений одинаковое количество шаблонов — используйте кнопку «Копировать из [название]» в боковой панели, чтобы перенести все дни месяца из уже заполненного отделения.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Объединение календарей: в Настройках пищеблока выберите «Объединить календарь с [отделение]». Тогда вкладка покажет оба названия, данные будут общие, а xlsx-файлы будут генерироваться для каждого отделения.', 'meal-menu' ); ?></li>
		</ul>
	</div>
</div>
