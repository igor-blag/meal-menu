<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Core {

	public static function init(): void {
		self::ensure_tables();

		$self = new self();
		add_action( 'init', array( $self, 'register_shortcodes' ) );
		add_action( 'admin_menu', array( $self, 'register_admin_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_admin_assets' ) );
		add_action( 'admin_footer', array( $self, 'output_docs_comment' ) );
		add_action( 'wp_enqueue_scripts', array( $self, 'enqueue_public_assets' ) );
		add_action( 'wp_ajax_meal_save_day', array( $self, 'ajax_save_day' ) );
		add_action( 'wp_ajax_meal_save_settings', array( $self, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_meal_save_oc', array( $self, 'ajax_save_oc' ) );
		add_action( 'wp_ajax_meal_upload_oc', array( $self, 'ajax_upload_oc' ) );
		add_action( 'wp_ajax_meal_list_files', array( $self, 'ajax_list_files' ) );
		add_action( 'wp_ajax_meal_save_theme', array( $self, 'ajax_save_theme' ) );
		add_action( 'phpmailer_init', array( $self, 'configure_smtp' ) );
		add_action( 'admin_post_meal_save_template', array( $self, 'handle_save_template' ) );
		add_action( 'admin_post_meal_add_template', array( $self, 'handle_add_template' ) );
		add_action( 'admin_post_meal_delete_template', array( $self, 'handle_delete_template' ) );
		add_action( 'admin_post_meal_import_confirm', array( $self, 'handle_import_confirm' ) );
		add_action( 'admin_post_meal_import_upload', array( $self, 'handle_import_upload' ) );
		add_action( 'meal_daily_check', array( $self, 'run_daily_cron' ) );
		add_filter( 'cron_schedules', array( $self, 'add_cron_schedules' ) );

		Roles::init();
	}

	public function register_shortcodes(): void {
		add_shortcode( 'meal_calendar', array( $this, 'shortcode_calendar' ) );
		add_shortcode( 'meal_day', array( $this, 'shortcode_day' ) );
		add_shortcode( 'meal_menu', array( $this, 'shortcode_menu' ) );
		add_shortcode( 'meal_oc', array( $this, 'shortcode_oc' ) );
	}

	public function register_admin_pages(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			return;
		}

		add_menu_page(
			__( 'Мониторинг питания', 'meal-menu' ),
			__( 'Питание', 'meal-menu' ),
			'manage_meal_menu',
			'meal-calendar',
			array( $this, 'render_calendar' ),
			'dashicons-food',
			30
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Календарь питания', 'meal-menu' ),
			__( 'Календарь', 'meal-menu' ),
			'manage_meal_menu',
			'meal-calendar',
			array( $this, 'render_calendar' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Шаблоны циклов', 'meal-menu' ),
			__( 'Шаблоны', 'meal-menu' ),
			'manage_meal_menu',
			'meal-templates',
			array( $this, 'render_templates' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Импорт меню', 'meal-menu' ),
			__( 'Импорт', 'meal-menu' ),
			'manage_meal_menu',
			'meal-import',
			array( $this, 'render_import' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Общественный контроль питания', 'meal-menu' ),
			__( 'ОК Питания', 'meal-menu' ),
			'manage_meal_menu',
			'meal-oc',
			array( $this, 'render_oc' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Настройки', 'meal-menu' ),
			__( 'Настройки', 'meal-menu' ),
			'manage_meal_menu',
			'meal-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Оформление', 'meal-menu' ),
			__( 'Оформление', 'meal-menu' ),
			'manage_meal_menu',
			'meal-theme',
			array( $this, 'render_theme' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Помощь', 'meal-menu' ),
			__( 'Помощь', 'meal-menu' ),
			'manage_meal_menu',
			'meal-help',
			array( $this, 'render_help' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Скачать файлы', 'meal-menu' ),
			__( 'Файлы', 'meal-menu' ),
			'manage_meal_menu',
			'meal-files',
			array( $this, 'render_files' )
		);
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, 'meal-' ) === false ) {
			return;
		}
		wp_enqueue_style( 'meal-menu-admin', MEAL_MENU_URL . 'assets/css/admin.css', array(), MEAL_MENU_VERSION );
		wp_enqueue_style( 'meal-menu-admin-themes', MEAL_MENU_URL . 'assets/css/themes.css', array( 'meal-menu-admin' ), MEAL_MENU_VERSION );
		wp_enqueue_script( 'meal-menu-admin', MEAL_MENU_URL . 'assets/js/admin.js', array(), MEAL_MENU_VERSION, true );
		wp_localize_script( 'meal-menu-admin', 'mealMenu', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'meal_menu_nonce' ),
		) );
	}

	public function enqueue_public_assets(): void {
		if ( ! is_singular() && ! is_page() ) {
			return;
		}
		global $post;
		if ( ! $post || ( ! has_shortcode( $post->post_content, 'meal_calendar' )
			&& ! has_shortcode( $post->post_content, 'meal_day' )
			&& ! has_shortcode( $post->post_content, 'meal_menu' )
			&& ! has_shortcode( $post->post_content, 'meal_oc' ) ) ) {
			return;
		}
		wp_enqueue_style( 'meal-menu-public', MEAL_MENU_URL . 'assets/css/public.css', array(), MEAL_MENU_VERSION );
		wp_enqueue_style( 'meal-menu-themes', MEAL_MENU_URL . 'assets/css/themes.css', array( 'meal-menu-public' ), MEAL_MENU_VERSION );
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
			/* translators: %s: filled days description */
			$subject = sprintf( __( 'Напоминание: меню заполнено на %d дн.', 'meal-menu' ), $filled );
			$message = '<p>' . __( 'Добрый день!', 'meal-menu' ) . '</p>'
				. '<p>' . sprintf( __( 'Меню в системе мониторинга питания %s вперёд.', 'meal-menu' ), $word ) . '</p>'
				. '<p>' . __( 'Пожалуйста, пополните расписание питания.', 'meal-menu' ) . '</p>';
			wp_mail( $admin_email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
	}

	public function render_calendar(): void {
		$this->render_admin_template( 'calendar' );
	}

	public function render_templates(): void {
		if ( isset( $_GET['id'] ) && (int) $_GET['id'] > 0 ) {
			$this->render_admin_template( 'template-edit' );
		} else {
			$this->render_admin_template( 'templates' );
		}
	}

	public function render_import(): void {
		$this->render_admin_template( 'import' );
	}

	public function render_oc(): void {
		$this->render_admin_template( 'oc' );
	}

	public function render_settings(): void {
		$this->render_admin_template( 'settings' );
	}

	public function render_help(): void {
		$this->render_admin_template( 'help' );
	}

	public function render_theme(): void {
		$this->render_admin_template( 'theme' );
	}

	public function render_files(): void {
		$this->render_admin_template( 'files' );
	}

	public function shortcode_calendar( array $atts = array(), string $content = '' ): string {
		ob_start();
		$atts = shortcode_atts( array( 'type' => '' ), $atts );
		require MEAL_MENU_DIR . 'templates/public/calendar.php';
		return ob_get_clean();
	}

	public function shortcode_day( array $atts = array(), string $content = '' ): string {
		ob_start();
		$atts = shortcode_atts( array( 'date' => '', 'type' => '' ), $atts );
		require MEAL_MENU_DIR . 'templates/public/day.php';
		return ob_get_clean();
	}

	public function shortcode_menu( array $atts = array(), string $content = '' ): string {
		ob_start();
		$atts = shortcode_atts( array( 'type' => '' ), $atts );
		require MEAL_MENU_DIR . 'templates/public/menu.php';
		return ob_get_clean();
	}

	public function shortcode_oc( array $atts = array(), string $content = '' ): string {
		ob_start();
		require MEAL_MENU_DIR . 'templates/public/oc.php';
		return ob_get_clean();
	}

	private function render_admin_template( string $template, string $edit_slug = '' ): void {
		$file = MEAL_MENU_DIR . 'templates/admin/page-' . $template . '.php';
		if ( file_exists( $file ) ) {
			$edit_slug_var = $edit_slug;
			require $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Template not found', 'meal-menu' ) . '</h1></div>';
		}
	}

	public function ajax_save_day(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-day.php';
	}

	public function ajax_save_settings(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-settings.php';
	}

	public function ajax_save_oc(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-oc.php';
	}

	public function ajax_upload_oc(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-upload-oc.php';
	}

	public function ajax_list_files(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-list-files.php';
	}

	public function ajax_save_theme(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$data = json_decode( file_get_contents( 'php://input' ), true );
		if ( $data ) {
			update_option( 'meal_theme_palette', sanitize_key( $data['palette'] ?? 'retro' ) );
			update_option( 'meal_theme_layout', sanitize_key( $data['layout'] ?? 'classic' ) );
			wp_send_json( array( 'ok' => true ) );
		}
		wp_send_json( array( 'ok' => false, 'error' => 'Invalid data' ) );
	}

	public function handle_save_template(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_save_template' );

		$id  = (int) ( $_POST['template_id'] ?? 0 );
		$db  = DB::instance();
		$tpl = $db->get_template( $id );
		if ( ! $tpl ) {
			wp_redirect( admin_url( 'admin.php?page=meal-templates' ) );
			exit;
		}

		$dept_info     = $db->get_department( $tpl['school_type'] );
		$forced_boarding = $dept_info && ! empty( $dept_info['is_boarding'] );
		$is_boarding   = $forced_boarding ? 1 : ( ! empty( $_POST['is_boarding'] ) ? 1 : 0 );

		$db->set_template_boarding( $id, $is_boarding );

		$meal_types = array( 'breakfast', 'breakfast2', 'lunch' );
		if ( $is_boarding ) {
			$meal_types[] = 'afternoon_snack';
			$meal_types[] = 'dinner';
			$meal_types[] = 'dinner2';
		}

		$all_items = array();
		foreach ( $meal_types as $meal ) {
			$sections  = $_POST[ $meal ]['section']    ?? array();
			$recipes   = $_POST[ $meal ]['recipe_num'] ?? array();
			$dishes    = $_POST[ $meal ]['dish_name']  ?? array();
			$grams     = $_POST[ $meal ]['grams']      ?? array();
			$prices    = $_POST[ $meal ]['price']      ?? array();
			$kcals     = $_POST[ $meal ]['kcal']       ?? array();
			$proteins  = $_POST[ $meal ]['protein']    ?? array();
			$fats      = $_POST[ $meal ]['fat']        ?? array();
			$carbs_arr = $_POST[ $meal ]['carbs']      ?? array();

			foreach ( $dishes as $i => $dish ) {
				$dish = trim( $dish );
				if ( $dish === '' ) {
					continue;
				}
				$all_items[] = array(
					'meal_type'  => $meal,
					'section'    => trim( $sections[ $i ]  ?? '' ),
					'recipe_num' => trim( $recipes[ $i ]   ?? '' ),
					'dish_name'  => $dish,
					'grams'      => ( $grams[ $i ]    ?? '' ) !== '' ? $grams[ $i ]    : null,
					'price'      => ( $prices[ $i ]   ?? '' ) !== '' ? $prices[ $i ]   : null,
					'kcal'       => ( $kcals[ $i ]    ?? '' ) !== '' ? $kcals[ $i ]    : null,
					'protein'    => ( $proteins[ $i ] ?? '' ) !== '' ? $proteins[ $i ] : null,
					'fat'        => ( $fats[ $i ]     ?? '' ) !== '' ? $fats[ $i ]     : null,
					'carbs'      => ( $carbs_arr[ $i ] ?? '' ) !== '' ? $carbs_arr[ $i ] : null,
				);
			}
		}

		$db->save_template_items( $id, $all_items );

		if ( $tpl['school_type'] === 'sm' ) {
			$approve_date = trim( $_POST['tm_approve_date'] ?? '' );
			if ( $approve_date ) {
				$db->save_tm_approve_date( $approve_date );
			}
			if ( class_exists( '\Meal_Menu\Excel_TM' ) ) {
				\Meal_Menu\Excel_TM::generate( 'sm', (int) current_time( 'Y' ) );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=meal-templates&id=' . $id . '&saved=1' ) );
		exit;
	}

	public function handle_add_template(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_add_template' );

		$type = sanitize_key( $_POST['type'] ?? 'sm' );
		$db   = DB::instance();
		$id   = $db->add_template( $type );

		wp_redirect( admin_url( 'admin.php?page=meal-templates&id=' . $id ) );
		exit;
	}

	public function handle_delete_template(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_delete_template' );

		$id   = (int) ( $_POST['id'] ?? 0 );
		$type = sanitize_key( $_POST['school_type'] ?? 'sm' );
		$db   = DB::instance();
		$db->delete_template( $id );

		wp_redirect( admin_url( 'admin.php?page=meal-templates&type=' . $type ) );
		exit;
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
		echo "  05. Настройки             → " . esc_url( $base . 'admin-settings.html' ) . "\n";
		echo "  06. ОК Питания            → " . esc_url( $base . 'admin-oc.html' ) . "\n";
		echo "  07. Импорт меню           → " . esc_url( $base . 'admin-import.html' ) . "\n";
		echo "  08. Публичная часть       → " . esc_url( $base . 'public-shortcodes.html' ) . "\n";
		echo "  09. Excel-генерация       → " . esc_url( $base . 'excel-generators.html' ) . "\n";
		echo "  10. Cron и Email          → " . esc_url( $base . 'cron-email.html' ) . "\n";
		echo "  11. Темы оформления       → " . esc_url( $base . 'themes.html' ) . "\n";
		echo "=================================================================\n";
		echo "  Таблицы БД: wp_meal_users, wp_meal_templates, wp_meal_items,\n";
		echo "  wp_meal_calendar, wp_meal_kitchen_settings, wp_meal_departments,\n";
		echo "  wp_meal_vacations, wp_meal_oc_monitoring, wp_meal_email_tokens\n";
		echo "  Классы: \\Meal_Menu\\DB, \\Meal_Menu\\Core, \\Meal_Menu\\Activator,\n";
		echo "  \\Meal_Menu\\Roles, \\Meal_Menu\\Excel_Daily/TM/KP/OC, \\Meal_Menu\\Importer\n";
		echo "=================================================================\n";
		echo "-->\n";
	}

	public function handle_import_upload(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_import_upload' );

		$tpl_id = (int) ( $_POST['template_id'] ?? 0 );
		$tpl    = $tpl_id ? DB::instance()->get_template( $tpl_id ) : null;
		if ( ! $tpl ) {
			wp_redirect( admin_url( 'admin.php?page=meal-import' ) );
			exit;
		}

		if ( ! isset( $_FILES['xlsx'] ) || $_FILES['xlsx']['error'] !== UPLOAD_ERR_OK ) {
			wp_redirect( admin_url( 'admin.php?page=meal-import&id=' . $tpl_id . '&error=upload' ) );
			exit;
		}

		$file = $_FILES['xlsx'];
		$allowed_mime = array(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/zip',
			'application/octet-stream',
		);

		if ( ! in_array( mime_content_type( $file['tmp_name'] ), $allowed_mime, true ) ) {
			wp_redirect( admin_url( 'admin.php?page=meal-import&id=' . $tpl_id . '&error=format' ) );
			exit;
		}

		try {
			$items = \Meal_Menu\Importer::parse( $file['tmp_name'] );
			if ( empty( $items ) ) {
				wp_redirect( admin_url( 'admin.php?page=meal-import&id=' . $tpl_id . '&error=empty' ) );
				exit;
			}

			$upload_dir = wp_upload_dir();
			$tmp_dir    = $upload_dir['basedir'] . '/meal-menu';
			wp_mkdir_p( $tmp_dir );
			$tmp_path   = $tmp_dir . '/tmp_import_' . get_current_user_id() . '.xlsx';
			file_put_contents( $tmp_path, file_get_contents( $file['tmp_name'] ) );
			set_transient( 'meal_import_tmp_' . get_current_user_id(), $tmp_path, HOUR_IN_SECONDS );
			set_transient( 'meal_import_tpl_' . get_current_user_id(), $tpl_id, HOUR_IN_SECONDS );

			set_transient( 'meal_import_preview_' . get_current_user_id(), $items, HOUR_IN_SECONDS );
			wp_redirect( admin_url( 'admin.php?page=meal-import&id=' . $tpl_id . '&preview=1' ) );
			exit;
		} catch ( \Exception $e ) {
			wp_redirect( admin_url( 'admin.php?page=meal-import&id=' . $tpl_id . '&error=parse' ) );
			exit;
		}
	}

	public function handle_import_confirm(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_import_confirm' );

		$tmp_path = get_transient( 'meal_import_tmp_' . get_current_user_id() );
		$save_tpl = (int) get_transient( 'meal_import_tpl_' . get_current_user_id() );

		if ( $tmp_path && file_exists( $tmp_path ) && $save_tpl ) {
			try {
				$items = \Meal_Menu\Importer::parse( $tmp_path );
				DB::instance()->save_template_items( $save_tpl, $items );
				unlink( $tmp_path );
				delete_transient( 'meal_import_tmp_' . get_current_user_id() );
				delete_transient( 'meal_import_tpl_' . get_current_user_id() );
				delete_transient( 'meal_import_preview_' . get_current_user_id() );
				wp_redirect( admin_url( 'admin.php?page=meal-templates&id=' . $save_tpl . '&imported=1' ) );
				exit;
			} catch ( \Exception $e ) {
				wp_redirect( admin_url( 'admin.php?page=meal-import&id=' . $save_tpl . '&error=save' ) );
				exit;
			}
		}
		wp_redirect( admin_url( 'admin.php?page=meal-import' ) );
		exit;
	}

	private static function ensure_tables(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'meal_templates';
		$exists = $wpdb->get_var( "SELECT name FROM sqlite_master WHERE type='table' AND name='$table'" );
		if ( ! $exists ) {
			Activator::activate();
		}
	}
}
