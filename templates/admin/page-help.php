<?php if ( ! defined( 'ABSPATH' ) ) exit; $db = \Meal_Menu\DB::instance(); $org_name = $db->get_org_name(); $inst_type = $db->get_institution_type(); ?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Справка', 'meal-menu' ); ?></h1>

	<div style="margin-bottom:36px;background:var(--wp-blue-pale,#f0f6fc);border-left:4px solid var(--wp-blue);padding:16px 20px;line-height:1.6">
		<p style="margin:0"><?php _e( 'Плагин «Меню питания» поддерживает работу как со <strong>школами</strong>, так и с <strong>детскими садами</strong>.', 'meal-menu' ); ?></p>
		<p style="margin:8px 0 0"><?php _e( 'Для школ: автоматизированная публикация файлов <strong>.xlsx</strong> для мониторинга ФЦМПО (ежедневное меню, типовое меню, годовой план-график), каникулы, общественный контроль питания.', 'meal-menu' ); ?></p>
		<p style="margin:8px 0 0"><?php _e( 'Для детских садов: только отображение меню на сайте, без Excel-файлов, каникул и ОК. Два типовых отделения — ясли и основная группа.', 'meal-menu' ); ?></p>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--wp-blue);padding-left:12px;margin-bottom:16px"><?php _e( 'Если плагин только что установлен', 'meal-menu' ); ?></h2>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div>
			<strong><?php _e( 'Выберите тип учреждения', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Перейдите в <strong>Питание → Настройки пищеблока</strong> и выберите «Школа» или «Детский сад». Типовые отделения создадутся автоматически.', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div>
			<strong><?php _e( 'Настройки пищеблока', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Настройте отделения, рабочие дни, учебный год и каникулы (для школы). Для детсада эти разделы скрыты.', 'meal-menu' ); ?></p>
			<p style="margin:4px 0 0;line-height:1.5;font-size:.85rem;color:var(--wp-muted)"><?php _e( 'Если у нескольких отделений одинаковая длина цикла — выберите «Объединить календарь с [отделение]».', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div>
			<strong><?php _e( 'Настройки плагина / ОК Питания', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Укажите папку публикации .xlsx, настройте SMTP. Для школ доступна страница «ОК Питания» — заполните разделы общественного контроля (ФИС ФРДО).', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div>
			<strong><?php _e( 'Шаблоны циклов', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Создайте наборы блюд по дням цикла вручную, импортом из .xlsx или из TM-файла (весь цикл).', 'meal-menu' ); ?></p>
		</div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:24px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">5</div>
		<div>
			<strong><?php _e( 'Календарь — заполнять каждый месяц', 'meal-menu' ); ?></strong>
			<p style="margin:2px 0 0;line-height:1.5"><?php _e( 'Ежемесячно назначайте дни цикла на конкретные даты. Из календаря генерируются файлы (для школы) или отображается меню на сайте.', 'meal-menu' ); ?></p>
		</div>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--wp-blue);padding-left:12px;margin-bottom:16px"><?php _e( 'Работа с календарём (каждый месяц)', 'meal-menu' ); ?></h2>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div style="line-height:1.5"><?php _e( 'Выберите <strong>вкладку отделения питания</strong>. Если в настройках включено объединение календарей — будет показана одна вкладка с названиями обоих отделений.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div style="line-height:1.5"><?php _e( 'Перейдите на нужный месяц стрелками. Серые ячейки до и после сетки показывают данные соседних месяцев для контекста.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div style="line-height:1.5"><?php _e( 'При необходимости отметьте <strong>выходные</strong> и <strong>рабочие дни</strong> кликом по ячейке. День, отмеченный рабочим, отображается на белом фоне с пометкой «Рабочий день».', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div style="line-height:1.5"><?php _e( 'Кликните на <strong>первый рабочий день</strong>, укажите <strong>номер дня меню</strong> (от какого числа цикла отталкиваться) и нажмите «Заполнить до конца месяца» (заполнит весь остаток) или «Применить» (только этот день).', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">5</div>
		<div style="line-height:1.5"><?php _e( 'Когда все дни месяца назначены — нажмите <strong>«Создать файлы»</strong>. Будут сгенерированы файлы ежедневного меню для этого и всех объединённых отделений.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">6</div>
		<div style="line-height:1.5"><?php _e( 'Если отделения не объединены — повторите шаги 2–5 для каждой вкладки. Если у отделения такой же цикл и оно уже заполнено — в боковой панели появится кнопка «Копировать из [название]».', 'meal-menu' ); ?></div>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--wp-blue);padding-left:12px;margin:32px 0 16px"><?php _e( 'Летний лагерь', 'meal-menu' ); ?></h2>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div style="line-height:1.5"><?php _e( 'Включите опцию <strong>«Летний лагерь»</strong> в Настройках пищеблока для отделения «Начальная школа».', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div style="line-height:1.5"><?php _e( 'Заполните даты начала и окончания лагеря, выберите рабочие дни недели.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div style="line-height:1.5"><?php _e( 'После включения появятся вкладки <strong>«Летний лагерь»</strong> на страницах Шаблоны и Календарь. Данные лагеря хранятся отдельно от основной школы.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div style="line-height:1.5"><?php _e( 'Создайте шаблоны циклов в <strong>Питание → Шаблоны → Летний лагерь</strong>.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">5</div>
		<div style="line-height:1.5"><?php _e( 'Заполните календарь в <strong>Питание → Календарь → Летний лагерь</strong> и сгенерируйте файлы.', 'meal-menu' ); ?></div>
	</div>

	<div style="margin-top:16px;padding:12px 16px;background:var(--wp-blue-pale,#f0f6fc);border-left:3px solid var(--wp-blue);line-height:1.6">
		<strong><?php _e( 'Примечание:', 'meal-menu' ); ?></strong>
		<?php _e( 'В календаре лагеря активны только даты внутри указанного интервала. Остальные затенены и недоступны для редактирования. Однодневные праздники (например, День России) отображаются как выходные.', 'meal-menu' ); ?>
		<?php _e( 'Файлы лагеря имеют те же имена, что и файлы Начальной школы (например, 2025-09-01-sm.xlsx), и перезаписывают их. В летний период на сайте отображается только календарь лагеря.', 'meal-menu' ); ?>
	</div>

	<div style="margin-top:20px;padding:12px 16px;background:var(--wp-blue-pale,#f0f6fc);border-left:3px solid var(--wp-blue);line-height:1.6">
		<strong><?php _e( 'Подсказки:', 'meal-menu' ); ?></strong>
		<ul style="margin:4px 0 0;padding-left:18px">
			<li><?php _e( 'Если в середине месяца график поменялся — кликните на любой день, измените номер меню и нажмите «Заполнить до конца месяца»: остаток пересчитается автоматически.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Кнопка «Заполнить [месяц]» заполняет только пустые дни, не меняя уже назначенные.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Если у отделений одинаковое количество шаблонов — используйте кнопку «Копировать из [название]» в боковой панели, чтобы перенести все дни месяца из уже заполненного отделения.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Объединение календарей: в Настройках пищеблока выберите «Объединить календарь с [отделение]». Тогда на странице календаря будет одна вкладка с общими данными, а xlsx-файлы будут генерироваться для каждого отделения со своим постфиксом.', 'meal-menu' ); ?></li>
			<li><?php _e( 'На странице Шаблоны — колонки приёмов пищи формируются динамически: показываются только те, в которых есть хотя бы одно блюдо. Полдник, Ужин и т.д. появятся автоматически.', 'meal-menu' ); ?></li>
			<li><?php _e( 'Дропзона на странице Шаблоны определяет тип файла автоматически: ежедневное меню → новый шаблон, TM-файл → замена всего цикла. Если бросить JPEG/PNG/WebP — фото отправится в AI-распознавание через подключённый AI-коннектор (Settings → Connectors).', 'meal-menu' ); ?></li>
			<li><?php _e( 'Множественное удаление: на странице Шаблоны отметьте чекбоксами нужные дни и нажмите «Удалить выбранные».', 'meal-menu' ); ?></li>
			<li><?php _e( 'В публичном календаре строки раздела меню (гор.блюдо, хлеб и т.п.) скрыты — только блюда и КБЖУ для простоты просмотра.', 'meal-menu' ); ?></li>
		</ul>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--wp-blue);padding-left:12px;margin:32px 0 16px"><?php _e( 'Блок Гутенберг «Календарь питания»', 'meal-menu' ); ?></h2>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div style="line-height:1.5"><?php _e( 'В редакторе страницы/поста нажмите <strong>«+»</strong> и найдите блок <strong>«Календарь питания»</strong>.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div style="line-height:1.5"><?php _e( 'Выберите <strong>тип школы</strong> (отделение). После выбора появится предпросмотр календаря.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div style="line-height:1.5"><?php _e( 'В боковой панели блока настройте <strong>палитру</strong> и <strong>макет</strong> оформления. Значение «По умолчанию» берёт глобальные настройки со страницы <strong>Питание → Оформление</strong>.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div style="line-height:1.5"><?php _e( 'При необходимости отключите спойлер <strong>«Общественный контроль питания»</strong> — он скроется на сайте. Для детского сада спойлер и тогл скрыты.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">5</div>
		<div style="line-height:1.5"><?php _e( 'Опубликуйте страницу. На сайте календарь будет работать как обычно: переключение месяцев, модалка с меню дня, спойлер ОК.', 'meal-menu' ); ?></div>
	</div>

	<div style="margin-top:16px;padding:12px 16px;background:var(--wp-blue-pale,#f0f6fc);border-left:3px solid var(--wp-blue);line-height:1.6">
		<strong><?php _e( 'Примечание:', 'meal-menu' ); ?></strong>
		<?php _e( 'Шорткод <code>[meal_calendar]</code> продолжает работать. Вы можете использовать как блок, так и шорткод — результат будет одинаковым.', 'meal-menu' ); ?>
	</div>

	<h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.08em;border-left:4px solid var(--wp-blue);padding-left:12px;margin:32px 0 16px"><?php _e( 'Импорт шаблона с фото (AI-распознавание)', 'meal-menu' ); ?></h2>

	<p style="line-height:1.6"><?php _e( 'Плагин использует AI-коннекторы WordPress 7.0+ (OpenRouter, Google AI, OpenAI, Anthropic) для распознавания меню с фотографии. Для работы требуется:', 'meal-menu' ); ?></p>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">1</div>
		<div style="line-height:1.5"><?php _e( 'Установить и настроить AI-коннектор в <strong>Settings → Connectors</strong> (OpenRouter, Google, OpenAI или Anthropic).', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">2</div>
		<div style="line-height:1.5"><?php _e( 'Установить AI-плагин из <strong>Плагины → Добавить новый</strong> (поиск «AI» от WordPress.org).', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">3</div>
		<div style="line-height:1.5"><?php _e( 'Одобрить доступ плагина к коннектору в <strong>Tools → Connector Approvals</strong>.', 'meal-menu' ); ?></div>
	</div>

	<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:10px">
		<div style="background:var(--wp-blue);color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px">4</div>
		<div style="line-height:1.5"><?php _e( 'Перетащите JPEG/PNG/WebP-фото меню в дропзону на странице <strong>Питание → Шаблоны</strong>. Фото отправится в AI, результат появится в модальном окне — отредактируйте и нажмите «Сохранить шаблон».', 'meal-menu' ); ?></div>
	</div>

	<div style="margin-top:16px;padding:12px 16px;background:#fcf0f1;border-left:3px solid var(--wp-error);line-height:1.6">
		<strong><?php _e( 'Важно:', 'meal-menu' ); ?></strong>
		<?php _e( 'Без настроенного коннектора AI-распознавание недоступно — на странице будет показан алерт с ссылкой на настройки. Все запросы логируются в <strong>Tools → AI Request Logs</strong>.', 'meal-menu' ); ?>
	</div>
</div>
