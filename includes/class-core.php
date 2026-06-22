<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Core {

	public static function init(): void {
		self::ensure_tables();

		new Shortcodes();
		new Admin();
		new Ajax();

		$self = new self();
		add_action( 'init', array( $self, 'register_blocks' ) );
		add_action( 'admin_footer', array( $self, 'output_docs_comment' ) );
		add_action( 'wp_enqueue_scripts', array( $self, 'enqueue_public_assets' ) );
		add_action( 'phpmailer_init', array( $self, 'configure_smtp' ) );
		add_action( 'meal_daily_check', array( $self, 'run_daily_cron' ) );
		add_filter( 'cron_schedules', array( $self, 'add_cron_schedules' ) );

		Roles::init();
	}

	public function register_blocks(): void {
		wp_register_style( 'meal-menu-public', MEAL_MENU_URL . 'assets/css/public.css', array(), MEAL_MENU_VERSION );
		wp_register_style( 'meal-menu-themes', MEAL_MENU_URL . 'assets/css/themes.css', array( 'meal-menu-public' ), MEAL_MENU_VERSION );

		$block_path = MEAL_MENU_DIR . 'blocks/meal-calendar';
		if ( ! file_exists( $block_path . '/block.json' ) ) {
			return;
		}

		$block = register_block_type( $block_path );

		if ( $block && ! empty( $block->editor_script ) ) {
			wp_localize_script( $block->editor_script, 'mealBlockData', array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'departments'    => DB::instance()->get_enabled_departments(),
				'currentYear'    => (int) current_time( 'Y' ),
				'currentMonth'   => (int) current_time( 'n' ),
				'isKindergarten' => DB::instance()->is_kindergarten(),
			) );
		}
	}

	public function enqueue_public_assets(): void {
		if ( ! is_singular() && ! is_page() ) {
			return;
		}
		global $post;
		if ( ! $post || ( ! has_shortcode( $post->post_content, 'meal_calendar' )
			&& ! has_shortcode( $post->post_content, 'meal_day' ) ) ) {
			return;
		}
		wp_enqueue_style( 'meal-menu-public', MEAL_MENU_URL . 'assets/css/public.css', array(), MEAL_MENU_VERSION );
		wp_enqueue_style( 'meal-menu-themes', MEAL_MENU_URL . 'assets/css/themes.css', array( 'meal-menu-public' ), MEAL_MENU_VERSION );
		wp_enqueue_script( 'meal-menu-public', MEAL_MENU_URL . 'assets/js/public.js', array(), MEAL_MENU_VERSION, true );
		wp_localize_script( 'meal-menu-public', 'mealMenu', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'meal_menu_nonce' ),
		) );
	}

	public function add_cron_schedules( array $schedules ): array {
		$schedules['meal_daily'] = array(
			'interval' => 86400,
			'display'  => __( 'Ежедневно', 'meal-menu' ),
		);
		return $schedules;
	}

	public function run_daily_cron(): void {
		$db        = DB::instance();
		$filled    = $db->count_filled_workdays_ahead( current_time( 'Y-m-d' ) );
		$admin_email = get_option( 'meal_admin_email', '' );

		if ( $filled <= 3 && ! empty( $admin_email ) ) {
			$word = match ( true ) {
				$filled === 0 => __( 'не заполнено ни на один день', 'meal-menu' ),
				$filled === 1 => __( 'заполнено только на 1 рабочий день', 'meal-menu' ),
				default       => sprintf( __( 'заполнено только на %d рабочих дня', 'meal-menu' ), $filled ),
			};
			$subject = sprintf( __( 'Напоминание: меню заполнено на %d дн.', 'meal-menu' ), $filled );
			$message = '<p>' . __( 'Добрый день!', 'meal-menu' ) . '</p>'
				. '<p>' . sprintf( __( 'Меню в системе мониторинга питания %s вперёд.', 'meal-menu' ), $word ) . '</p>'
				. '<p>' . __( 'Пожалуйста, пополните расписание питания.', 'meal-menu' ) . '</p>';
			wp_mail( $admin_email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
	}

	public function configure_smtp( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
		$host = get_option( 'meal_smtp_host', '' );
		if ( empty( $host ) ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = (int) get_option( 'meal_smtp_port', 587 );
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = get_option( 'meal_smtp_user', '' );
		$phpmailer->Password   = get_option( 'meal_smtp_pass', '' );
		$phpmailer->SMTPSecure = get_option( 'meal_smtp_secure', '' );

		$from     = get_option( 'meal_mail_from', '' );
		$from_name = get_option( 'meal_mail_from_name', '' );
		if ( $from ) {
			$phpmailer->setFrom( $from, $from_name );
		}
	}

	public function output_docs_comment(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'meal-' ) === false ) {
			return;
		}
		$base = MEAL_MENU_URL . 'docs/';
		echo "\n<!--\n";
		echo "=================================================================\n";
		echo "  Документация плагина «Мониторинг питания»\n";
		echo "  Полная версия: " . esc_url( $base . 'main.html' ) . "\n";
		echo "=================================================================\n";
		echo "  Модули:\n";
		echo "  01. Активация и БД        → " . esc_url( $base . 'activator-db.html' ) . "\n";
		echo "  02. Ядро плагина          → " . esc_url( $base . 'core.html' ) . "\n";
		echo "  03. Календарь питания     → " . esc_url( $base . 'admin-calendar.html' ) . "\n";
		echo "  04. Шаблоны циклов        → " . esc_url( $base . 'admin-templates.html' ) . "\n";
		echo "  05. Настройки пищеблока   → " . esc_url( $base . 'admin-settings.html' ) . "\n";
		echo "  06. Настройки плагина     → " . esc_url( $base . 'plugin-settings.html' ) . "\n";
		echo "  07. ОК Питания            → " . esc_url( $base . 'admin-oc.html' ) . "\n";
		echo "  08. Публичная часть       → " . esc_url( $base . 'public-shortcodes.html' ) . "\n";
		echo "  10. Excel-генерация       → " . esc_url( $base . 'excel-generators.html' ) . "\n";
		echo "  11. Cron и Email          → " . esc_url( $base . 'cron-email.html' ) . "\n";
		echo "  12. Темы оформления       → " . esc_url( $base . 'themes.html' ) . "\n";
		echo "  13. Публикация в /food/   → " . esc_url( $base . 'publish-food.html' ) . "\n";
		echo "  14. Летний лагерь          → " . esc_url( $base . 'summer-camp.html' ) . "\n";
		echo "  15. Импорт/Экспорт данных → " . esc_url( $base . 'plugin-settings.html#import-export' ) . "\n";
		echo "  16. Сброс данных          → " . esc_url( $base . 'plugin-settings.html#reset' ) . "\n";
		echo "=================================================================\n";
		echo "  Таблицы БД: wp_meal_users, wp_meal_templates, wp_meal_items,\n";
		echo "  wp_meal_calendar, wp_meal_kitchen_settings, wp_meal_departments,\n";
		echo "  wp_meal_vacations, wp_meal_oc_monitoring, wp_meal_email_tokens,\n";
		echo "  wp_meal_camp_templates, wp_meal_camp_items, wp_meal_camp_calendar\n";
		echo "  Классы: \\Meal_Menu\\DB, \\Meal_Menu\\Core, \\Meal_Menu\\Activator,\n";
		echo "  \\Meal_Menu\\Roles, \\Meal_Menu\\Shortcodes, \\Meal_Menu\\Admin, \\Meal_Menu\\Ajax,\n";
		echo "  \\Meal_Menu\\Excel_Daily/TM/KP/OC, \\Meal_Menu\\Importer\n";
		echo "=================================================================\n";
		echo "-->\n";
	}

	private static function ensure_tables(): void {
		global $wpdb;

		$p = $wpdb->prefix . 'meal_';

		$table_exists = function ( string $table ) use ( $wpdb ): bool {
			return ! empty( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) );
		};
		$has_column = function ( string $table, string $col ) use ( $wpdb, $table_exists ): bool {
			if ( ! $table_exists( $table ) ) {
				return false;
			}
			return ! empty( $wpdb->get_results( "SHOW COLUMNS FROM `$table` LIKE '$col'" ) );
		};

		if ( ! $table_exists( $p . 'templates' ) ) {
			Activator::activate();
		}
		if ( ! $table_exists( $p . 'holidays' ) ) {
			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$p}holidays (
				id int(10) unsigned NOT NULL auto_increment,
				label varchar(100) NOT NULL,
				month_day varchar(5) NOT NULL,
				month_day_to varchar(5) default NULL,
				created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
				PRIMARY KEY  (id)
			)" );
		}
		if ( $table_exists( $p . 'holidays' ) && ! $has_column( $p . 'holidays', 'month_day_to' ) ) {
			$wpdb->query( "ALTER TABLE {$p}holidays ADD COLUMN month_day_to varchar(5) default NULL" );
		}
		if ( $table_exists( $p . 'calendar' ) && ! $has_column( $p . 'calendar', 'iterate_number' ) ) {
			$wpdb->query( "ALTER TABLE {$p}calendar ADD COLUMN iterate_number integer NOT NULL DEFAULT 0" );
		}
		if ( ! $table_exists( $p . 'camp_templates' ) ) {
			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$p}camp_templates (
				id int(10) unsigned NOT NULL auto_increment,
				day_number tinyint(3) unsigned NOT NULL,
				school_type varchar(20) NOT NULL default 'sm',
				is_boarding tinyint(1) NOT NULL default 0,
				label varchar(100) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY uq_day_type (day_number, school_type)
			)" );
		}

		if ( ! $table_exists( $p . 'camp_items' ) ) {
			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$p}camp_items (
				id int(10) unsigned NOT NULL auto_increment,
				template_id int(10) unsigned NOT NULL,
				meal_type varchar(20) NOT NULL default 'breakfast',
				section varchar(50) default NULL,
				recipe_num varchar(30) default NULL,
				dish_name varchar(255) default NULL,
				grams decimal(8,1) default NULL,
				price decimal(8,2) default NULL,
				kcal decimal(8,2) default NULL,
				protein decimal(8,2) default NULL,
				fat decimal(8,2) default NULL,
				carbs decimal(8,2) default NULL,
				sort_order smallint NOT NULL default 0,
				PRIMARY KEY  (id),
				KEY template_id (template_id)
			)" );
		}

		if ( ! $table_exists( $p . 'camp_calendar' ) ) {
			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$p}camp_calendar (
				date date NOT NULL,
				school_type varchar(20) NOT NULL default 'sm',
				template_id int(10) unsigned default NULL,
				school varchar(100) default NULL,
				dept varchar(50) default NULL,
				is_cycle_start tinyint(1) NOT NULL default 0,
				iterate_number tinyint(1) NOT NULL default 0,
				PRIMARY KEY  (date, school_type),
				KEY template_id (template_id)
			)" );
		}

		if ( $table_exists( $p . 'kitchen_settings' ) && ! $has_column( $p . 'kitchen_settings', 'institution_type' ) ) {
			$wpdb->query( "ALTER TABLE {$p}kitchen_settings ADD COLUMN institution_type varchar(20) NOT NULL DEFAULT 'school'" );
		}

		if ( $table_exists( $p . 'departments' ) && ! $has_column( $p . 'departments', 'has_summer_camp' ) ) {
			$wpdb->query( "ALTER TABLE {$p}departments ADD COLUMN has_summer_camp tinyint(1) NOT NULL default 0" );
			$wpdb->query( "ALTER TABLE {$p}departments ADD COLUMN camp_start_date date default NULL" );
			$wpdb->query( "ALTER TABLE {$p}departments ADD COLUMN camp_end_date date default NULL" );
			$wpdb->query( "ALTER TABLE {$p}departments ADD COLUMN camp_workdays varchar(20) NOT NULL default '1,2,3,4,5'" );
			$wpdb->query( "ALTER TABLE {$p}departments ADD COLUMN camp_is_boarding tinyint(1) NOT NULL default 0" );
			$wpdb->query( "ALTER TABLE {$p}departments ADD COLUMN camp_publish_xlsx tinyint(1) NOT NULL default 0" );
		}
	}
}
