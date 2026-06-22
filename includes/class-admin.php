<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_meal_save_template', array( $this, 'handle_save_template' ) );
		add_action( 'admin_post_meal_add_template', array( $this, 'handle_add_template' ) );
		add_action( 'admin_post_meal_delete_template', array( $this, 'handle_delete_template' ) );
		add_action( 'admin_post_meal_export_data', array( $this, 'handle_export_data' ) );
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

		if ( ! DB::instance()->is_kindergarten() ) {
			add_submenu_page(
				'meal-calendar',
				__( 'Общественный контроль питания', 'meal-menu' ),
				__( 'ОК Питания', 'meal-menu' ),
				'manage_meal_menu',
				'meal-oc',
				array( $this, 'render_oc' )
			);
		}

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

	private function render_admin_template( string $template, string $edit_slug = '' ): void {
		$file = MEAL_MENU_DIR . 'templates/admin/page-' . $template . '.php';
		if ( file_exists( $file ) ) {
			$edit_slug_var = $edit_slug;
			require $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Template not found', 'meal-menu' ) . '</h1></div>';
		}
	}

	public function handle_save_template(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_save_template' );

		$id      = (int) ( $_POST['template_id'] ?? 0 );
		$is_camp = ! empty( $_POST['is_camp'] );
		$db      = DB::instance();

		$tpl = $is_camp ? $db->get_camp_template( $id ) : $db->get_template( $id );
		if ( ! $tpl ) {
			wp_redirect( admin_url( 'admin.php?page=meal-templates' ) );
			exit;
		}

		$dept_info       = $db->get_department( $tpl['school_type'] );
		$forced_boarding = $is_camp ? ( $dept_info && ! empty( $dept_info['camp_is_boarding'] ) ) : ( $dept_info && ! empty( $dept_info['is_boarding'] ) );
		$is_boarding     = $forced_boarding ? 1 : ( ! empty( $_POST['is_boarding'] ) ? 1 : 0 );

		if ( $is_camp ) {
			$db->set_camp_template_boarding( $id, $is_boarding );
		} else {
			$db->set_template_boarding( $id, $is_boarding );
		}

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

		if ( $is_camp ) {
			$db->save_camp_template_items( $id, $all_items );
		} else {
			$db->save_template_items( $id, $all_items );
		}

		if ( ! $db->is_kindergarten() && $tpl['school_type'] === 'sm' && ! $is_camp ) {
			$approve_date = trim( $_POST['tm_approve_date'] ?? '' );
			if ( $approve_date ) {
				$db->save_tm_approve_date( $approve_date );
			}
			if ( class_exists( '\Meal_Menu\Excel_TM' ) ) {
				\Meal_Menu\Excel_TM::generate( 'sm', (int) current_time( 'Y' ) );
			}
		}
		if ( ! $db->is_kindergarten() && $is_camp && class_exists( '\Meal_Menu\Excel_TM' ) ) {
			\Meal_Menu\Excel_TM::generate( 'sm', (int) current_time( 'Y' ), true );
		}

		wp_redirect( admin_url( 'admin.php?page=meal-templates&id=' . $id . '&saved=1' . ( $is_camp ? '&camp=1' : '' ) ) );
		exit;
	}

	public function handle_add_template(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_add_template' );

		$type    = sanitize_key( $_POST['type'] ?? 'sm' );
		$is_camp = ! empty( $_POST['camp'] );
		$db      = DB::instance();
		$id      = $is_camp ? $db->add_camp_template( $type ) : $db->add_template( $type );

		wp_redirect( admin_url( 'admin.php?page=meal-templates&id=' . $id . ( $is_camp ? '&camp=1' : '' ) ) );
		exit;
	}

	public function handle_delete_template(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_delete_template' );

		$id      = (int) ( $_POST['id'] ?? 0 );
		$type    = sanitize_key( $_POST['school_type'] ?? 'sm' );
		$is_camp = ! empty( $_POST['camp'] );
		$db      = DB::instance();
		if ( $is_camp ) {
			$db->delete_camp_template( $id );
		} else {
			$db->delete_template( $id );
		}

		wp_redirect( admin_url( 'admin.php?page=meal-templates&type=' . $type . ( $is_camp ? '&camp=1' : '' ) ) );
		exit;
	}

	public function handle_export_data(): void {
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'meal_export_data' );

		global $wpdb;
		$p     = $wpdb->prefix . 'meal_';
		$tables = array( 'templates', 'items', 'calendar', 'camp_templates', 'camp_items', 'camp_calendar', 'kitchen_settings', 'departments', 'vacations', 'oc_monitoring', 'users', 'email_tokens' );

		$data = array();
		foreach ( $tables as $table ) {
			$data[ $table ] = $wpdb->get_results( "SELECT * FROM {$p}{$table}", ARRAY_A ) ?: array();
		}

		$options = array();
		foreach ( array( 'meal_admin_email', 'meal_mail_from', 'meal_mail_from_name', 'meal_smtp_host', 'meal_smtp_user', 'meal_smtp_pass', 'meal_smtp_port', 'meal_smtp_secure', 'meal_food_dir', 'meal_theme_palette', 'meal_theme_layout', 'meal_delete_after_days', 'meal_institution_type' ) as $opt ) {
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
}
