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
		add_action( 'wp_ajax_meal_import_dropzone', array( $self, 'ajax_import_dropzone' ) );
		add_action( 'wp_ajax_meal_delete_file', array( $self, 'ajax_delete_file' ) );
		add_action( 'wp_ajax_meal_cleanup_files', array( $self, 'ajax_cleanup_files' ) );
		add_action( 'wp_ajax_meal_get_day_menu', array( $self, 'ajax_get_day_menu' ) );
		add_action( 'wp_ajax_nopriv_meal_get_day_menu', array( $self, 'ajax_get_day_menu' ) );
		add_action( 'phpmailer_init', array( $self, 'configure_smtp' ) );
		add_action( 'admin_post_meal_save_template', array( $self, 'handle_save_template' ) );
		add_action( 'admin_post_meal_add_template', array( $self, 'handle_add_template' ) );
		add_action( 'admin_post_meal_delete_template', array( $self, 'handle_delete_template' ) );
		add_action( 'admin_post_meal_export_data', array( $self, 'handle_export_data' ) );
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
			__( 'Общественный контроль питания', 'meal-menu' ),
			__( 'ОК Питания', 'meal-menu' ),
			'manage_meal_menu',
			'meal-oc',
			array( $this, 'render_oc' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Настройки пищеблока', 'meal-menu' ),
			__( 'Настройки пищеблока', 'meal-menu' ),
			'manage_meal_menu',
			'meal-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'meal-calendar',
			__( 'Настройки плагина', 'meal-menu' ),
			__( 'Настройки плагина', 'meal-menu' ),
			'manage_meal_menu',
			'meal-plugin-settings',
			array( $this, 'render_plugin_settings' )
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
		wp_enqueue_style( 'meal-menu-public', MEAL_MENU_URL . 'assets/css/public.css', array(), MEAL_MENU_VERSION );
		wp_enqueue_style( 'meal-menu-admin-themes', MEAL_MENU_URL . 'assets/css/themes.css', array( 'meal-menu-public' ), MEAL_MENU_VERSION );
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

	public function render_oc(): void {
		$this->render_admin_template( 'oc' );
	}

	public function render_settings(): void {
		$this->render_admin_template( 'kitchen-settings' );
	}

	public function render_plugin_settings(): void {
		$this->render_admin_template( 'plugin-settings' );
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
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-day.php';
	}

	public function ajax_get_day_menu(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		$date = $_GET['date'] ?? '';
		$type = $_GET['type'] ?? '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! $type ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-get-day-menu.php';
	}

	public function ajax_save_settings(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		$nonce = $data['nonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
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

	public function ajax_delete_file(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$files = isset( $data['files'] ) && is_array( $data['files'] ) ? $data['files'] : array( $data['file'] ?? '' );
		$upload_dir = wp_upload_dir();
		$deleted = 0;
		$errors = array();
		foreach ( $files as $filename ) {
			$filename = basename( $filename );
			if ( ! $filename ) continue;
			$filepath = $upload_dir['basedir'] . '/meal-menu/' . $filename;
			if ( ! is_file( $filepath ) ) {
				$errors[] = sprintf( __( 'Файл не найден: %s', 'meal-menu' ), $filename );
				continue;
			}
			if ( unlink( $filepath ) ) {
				$deleted++;
				$food_path = meal_food_dir() . $filename;
				if ( is_file( $food_path ) ) {
					unlink( $food_path );
				}
			} else {
				$errors[] = sprintf( __( 'Не удалось удалить: %s', 'meal-menu' ), $filename );
			}
		}
		wp_send_json( array(
			'ok'      => true,
			'deleted' => $deleted,
			'errors'  => $errors,
		) );
	}

	public function ajax_cleanup_files(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$days  = max( 1, (int) ( $data['days'] ?? 15 ) );
		$cutoff = time() - ( $days * 86400 );
		$deleted = 0;
		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		foreach ( glob( $meal_dir . '/*.xlsx' ) ?: array() as $filepath ) {
			$name = basename( $filepath );
			if ( str_starts_with( $name, 'kp' ) || str_starts_with( $name, 'tm' ) || str_starts_with( $name, 'findex' ) ) {
				continue;
			}
			if ( filemtime( $filepath ) < $cutoff ) {
				unlink( $filepath );
				$deleted++;
				$food_path = meal_food_dir() . $name;
				if ( is_file( $food_path ) ) {
					unlink( $food_path );
				}
			}
		}
		wp_send_json( array( 'ok' => true, 'deleted' => $deleted ) );
	}

	public function ajax_save_theme(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true );
		if ( ! $data || ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		update_option( 'meal_theme_palette', sanitize_key( $data['palette'] ?? 'retro' ) );
		update_option( 'meal_theme_layout', sanitize_key( $data['layout'] ?? 'classic' ) );
		wp_send_json( array( 'ok' => true ) );
	}

	public function ajax_import_dropzone(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$type = sanitize_key( $_POST['type'] ?? 'sm' );
		if ( ! isset( $_FILES['xlsx'] ) || $_FILES['xlsx']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка загрузки файла', 'meal-menu' ) ) );
		}

		$allowed = array(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/zip',
			'application/octet-stream',
		);
		if ( ! in_array( mime_content_type( $_FILES['xlsx']['tmp_name'] ), $allowed, true ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный формат файла', 'meal-menu' ) ) );
		}

		try {
			$items = \Meal_Menu\Importer::parse( $_FILES['xlsx']['tmp_name'] );
			if ( empty( $items ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'В файле нет блюд', 'meal-menu' ) ) );
			}

			$db    = DB::instance();
			$tpl_id = $db->add_template( $type );
			$db->save_template_items( $tpl_id, $items );

			wp_send_json( array( 'ok' => true, 'id' => $tpl_id ) );
		} catch ( \Exception $e ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка обработки файла', 'meal-menu' ) ) );
		}
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
		echo "  05. Настройки пищеблока   → " . esc_url( $base . 'admin-settings.html' ) . "\n";
		echo "  06. Настройки плагина     → " . esc_url( $base . 'plugin-settings.html' ) . "\n";
		echo "  07. ОК Питания            → " . esc_url( $base . 'admin-oc.html' ) . "\n";
		echo "  08. Публичная часть       → " . esc_url( $base . 'public-shortcodes.html' ) . "\n";
		echo "  10. Excel-генерация       → " . esc_url( $base . 'excel-generators.html' ) . "\n";
		echo "  11. Cron и Email          → " . esc_url( $base . 'cron-email.html' ) . "\n";
		echo "  12. Темы оформления       → " . esc_url( $base . 'themes.html' ) . "\n";
		echo "  13. Публикация в /food/   → " . esc_url( $base . 'publish-food.html' ) . "\n";
		echo "  14. Импорт/Экспорт данных → " . esc_url( $base . 'plugin-settings.html#import-export' ) . "\n";
		echo "  15. Сброс данных          → " . esc_url( $base . 'plugin-settings.html#reset' ) . "\n";
		echo "=================================================================\n";
		echo "  Таблицы БД: wp_meal_users, wp_meal_templates, wp_meal_items,\n";
		echo "  wp_meal_calendar, wp_meal_kitchen_settings, wp_meal_departments,\n";
		echo "  wp_meal_vacations, wp_meal_oc_monitoring, wp_meal_email_tokens\n";
		echo "  Классы: \\Meal_Menu\\DB, \\Meal_Menu\\Core, \\Meal_Menu\\Activator,\n";
		echo "  \\Meal_Menu\\Roles, \\Meal_Menu\\Excel_Daily/TM/KP/OC, \\Meal_Menu\\Importer\n";
		echo "=================================================================\n";
		echo "-->\n";
	}

	public function handle_export_data(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_export_data' );

		global $wpdb;
		$p     = $wpdb->prefix . 'meal_';
		$tables = array( 'templates', 'items', 'calendar', 'kitchen_settings', 'departments', 'vacations', 'oc_monitoring', 'users', 'email_tokens' );

		$data = array();
		foreach ( $tables as $table ) {
			$data[ $table ] = $wpdb->get_results( "SELECT * FROM {$p}{$table}", ARRAY_A ) ?: array();
		}

		$options = array();
		foreach ( array( 'meal_admin_email', 'meal_mail_from', 'meal_mail_from_name', 'meal_smtp_host', 'meal_smtp_user', 'meal_smtp_pass', 'meal_smtp_port', 'meal_smtp_secure', 'meal_food_dir', 'meal_theme_palette', 'meal_theme_layout', 'meal_delete_after_days' ) as $opt ) {
			$val = get_option( $opt, null );
			if ( $val !== null ) {
				$options[ $opt ] = $val;
			}
		}
		$data['_options'] = $options;

		$filename = 'meal-menu-' . current_time( 'Y-m-d' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	private static function ensure_tables(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'meal_templates';
		$exists = $wpdb->get_var( "SELECT name FROM sqlite_master WHERE type='table' AND name='$table'" );
		if ( ! $exists ) {
			Activator::activate();
		}
		$v = $wpdb->prefix . 'meal_vacations';
		$col = $wpdb->get_results( "SELECT * FROM pragma_table_info('$v') WHERE name='actual_date'" );
		if ( empty( $col ) ) {
			$wpdb->query( "ALTER TABLE $v ADD COLUMN actual_date date NULL default NULL" );
		}
		$c = $wpdb->prefix . 'meal_calendar';
		$col = $wpdb->get_results( "SELECT * FROM pragma_table_info('$c') WHERE name='iterate_number'" );
		if ( empty( $col ) ) {
			$wpdb->query( "ALTER TABLE $c ADD COLUMN iterate_number integer NOT NULL DEFAULT 0" );
		}
	}
}
