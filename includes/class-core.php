<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Core {

	public static function init(): void {
		self::ensure_tables();

		$self = new self();
		add_action( 'init', array( $self, 'register_shortcodes' ) );
		add_action( 'init', array( $self, 'register_blocks' ) );
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
		add_action( 'wp_ajax_meal_import_tm', array( $self, 'ajax_import_tm' ) );
		add_action( 'wp_ajax_meal_bulk_delete_templates', array( $self, 'ajax_bulk_delete_templates' ) );
		add_action( 'wp_ajax_meal_delete_file', array( $self, 'ajax_delete_file' ) );
		add_action( 'wp_ajax_meal_cleanup_files', array( $self, 'ajax_cleanup_files' ) );
		add_action( 'wp_ajax_meal_get_day_menu', array( $self, 'ajax_get_day_menu' ) );
		add_action( 'wp_ajax_nopriv_meal_get_day_menu', array( $self, 'ajax_get_day_menu' ) );
		add_action( 'wp_ajax_meal_get_calendar', array( $self, 'ajax_get_calendar' ) );
		add_action( 'wp_ajax_nopriv_meal_get_calendar', array( $self, 'ajax_get_calendar' ) );
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
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'departments'  => DB::instance()->get_enabled_departments(),
				'currentYear'  => (int) current_time( 'Y' ),
				'currentMonth' => (int) current_time( 'n' ),
			) );
		}
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
		$atts = shortcode_atts( array(
			'type'    => '',
			'palette' => '',
			'layout'  => '',
			'show_oc' => true,
			'camp'    => null,
		), $atts );
		$db   = \Meal_Menu\DB::instance();

		$enabled_depts = $db->get_enabled_departments();
		$valid_types   = array_column( $enabled_depts, 'code' );

		$req_type   = $_GET['meal_type'] ?? '';
		$is_camp    = $atts['camp'] === '1' || $atts['camp'] === true || $atts['camp'] === 1 || isset( $_GET['meal_camp'] );
		$req_camp   = isset( $_GET['meal_camp'] ) ? ( $_GET['meal_camp'] === '1' ) : $is_camp;

		$type = $req_type && in_array( $req_type, $valid_types, true ) ? $req_type : ( $atts['type'] && in_array( $atts['type'], $valid_types, true ) ? $atts['type'] : ( $valid_types[0] ?? 'sm' ) );

		// Auto-detect camp mode
		if ( ! $req_camp && $db->is_camp_period_for_month( $type, (int) ( $_GET['meal_y'] ?? current_time( 'Y' ) ), (int) ( $_GET['meal_m'] ?? current_time( 'n' ) ) ) ) {
			$any_normal = false;
			foreach ( $enabled_depts as $dep ) {
				if ( ! $db->is_camp_period_for_month( $dep['code'], (int) ( $_GET['meal_y'] ?? current_time( 'Y' ) ), (int) ( $_GET['meal_m'] ?? current_time( 'n' ) ) ) ) {
					$any_normal = true;
					break;
				}
			}
			if ( ! $any_normal ) {
				$req_camp = true;
			}
		}

		$today_dt = new \DateTimeImmutable( current_time( 'Y-m-d' ) );
		$year  = (int) ( $_GET['meal_y'] ?? $today_dt->format( 'Y' ) );
		$month = (int) ( $_GET['meal_m'] ?? $today_dt->format( 'n' ) );

		$palette = $atts['palette'] ?: get_option( 'meal_theme_palette', 'retro' );
		$layout  = $atts['layout'] ?: get_option( 'meal_theme_layout', 'classic' );
		$show_oc = filter_var( $atts['show_oc'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="meal-wrapper palette-<?php echo esc_attr( $palette ); ?> layout-<?php echo esc_attr( $layout ); ?>" id="meal-calendar-root">
			<div class="meal-container">
				<div class="meal-title"><?php _e( 'Календарь питания', 'meal-menu' ); ?></div>
				<div id="meal-calendar-body"><?php echo self::render_calendar_body( $type, $year, $month, $req_camp ); ?></div>
				<?php if ( $show_oc ) { echo self::render_oc_content(); } ?>
			</div>

			<div id="meal-modal" class="meal-modal-overlay" style="display:none">
				<div class="meal-modal-dialog">
					<div class="meal-modal-header">
						<span class="meal-modal-title" id="meal-modal-title"></span>
						<button class="meal-modal-close" id="meal-modal-close">&times;</button>
					</div>
					<div class="meal-modal-body" id="meal-modal-body">
						<div class="meal-modal-loader"><?php _e( 'Загрузка…', 'meal-menu' ); ?></div>
					</div>
				</div>
			</div>

			<footer class="meal-footer">
				<a href="https://github.com/igor-blag/web-food" target="_blank" rel="noopener">github.com/igor-blag/web-food</a>
			</footer>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function render_calendar_body( string $type, int $year, int $month, bool $is_camp = false ): string {
		$db = \Meal_Menu\DB::instance();

		if ( $is_camp ) {
			$enabled_depts = $db->get_enabled_departments();
			$camp_types    = array();
			foreach ( $enabled_depts as $dep ) {
				if ( ! empty( $dep['has_summer_camp'] ) ) {
					$camp_types[] = $dep['code'];
				}
			}
			if ( empty( $camp_types ) ) {
				$camp_types = array( $type );
			}
			$valid_types = $camp_types;
			$type_labels = array_combine( $camp_types, array_map( function( $c ) {
				return 'Летний лагерь';
			}, $camp_types ) );
			$dept = $db->get_department( $type );
			$real_type = $type;

			$cal_rows = array();
			$c = $db->get_table_name( 'camp_calendar' );
			$t = $db->get_table_name( 'camp_templates' );
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.date, c.template_id, mt.day_number, mt.label
				 FROM $c c
				 LEFT JOIN $t mt ON mt.id = c.template_id
				 WHERE c.school_type = %s AND c.date >= %s AND c.date <= %s",
				$real_type,
				sprintf( '%04d-%02d-01', $year, $month ),
				gmdate( 'Y-m-t', strtotime( sprintf( '%04d-%02d-01', $year, $month ) ) )
			), ARRAY_A ) ?: array();
			foreach ( $rows as $r ) {
				$cal_rows[ $r['date'] ] = $r;
			}
		} else {
			$enabled_depts = $db->get_enabled_departments();
			$valid_types   = array_column( $enabled_depts, 'code' );
			$type_labels   = array_combine( array_column( $enabled_depts, 'code' ), array_column( $enabled_depts, 'label' ) );

			// Filter out departments that are in camp period (they will show as camp tab)
			$filtered_labels = array();
			foreach ( $type_labels as $t => $label ) {
				if ( ! $db->is_camp_period_for_month( $t, $year, $month ) ) {
					$filtered_labels[ $t ] = $label;
				}
			}
			if ( ! empty( $filtered_labels ) ) {
				$type_labels = $filtered_labels;
				$valid_types = array_keys( $filtered_labels );
			}

			$dept = $db->get_department( $type );
			$real_type = ( $dept && ! empty( $dept['merged_with'] ) ) ? $dept['merged_with'] : $type;

			$cal_rows = array();
			$c = $db->get_table_name( 'calendar' );
			$t = $db->get_table_name( 'templates' );
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.date, c.template_id, mt.day_number, mt.label
				 FROM $c c
				 LEFT JOIN $t mt ON mt.id = c.template_id
				 WHERE c.school_type = %s AND c.date >= %s AND c.date <= %s",
				$real_type,
				sprintf( '%04d-%02d-01', $year, $month ),
				gmdate( 'Y-m-t', strtotime( sprintf( '%04d-%02d-01', $year, $month ) ) )
			), ARRAY_A ) ?: array();
			foreach ( $rows as $r ) {
				$cal_rows[ $r['date'] ] = $r;
			}
		}

		$today_dt = new \DateTimeImmutable( current_time( 'Y-m-d' ) );
		$req_dt   = new \DateTimeImmutable( "$year-$month-01" );
		$days_in_month = (int) $req_dt->format( 't' );
		$first_dow     = (int) $req_dt->format( 'N' );

		$prev_dt = $req_dt->modify( '-1 month' );
		$next_dt = $req_dt->modify( '+1 month' );
		$today   = $today_dt->format( 'Y-m-d' );

		$month_from  = sprintf( '%04d-%02d-01', $year, $month );
		$month_to    = sprintf( '%04d-%02d-%02d', $year, $month, $days_in_month );
		$vacation_days = $db->get_vacation_days_for_range( $month_from, $month_to );
		if ( $dept && ! empty( $dept['ignore_vacations'] ) ) {
			$vacation_days = array();
		}

		$month_names = array( 1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь' );

		// If camp is active and no other tabs show, show camp as default
		if ( ! $is_camp && empty( $type_labels ) ) {
			return self::render_calendar_body( $type, $year, $month, true );
		}

		// If requesting camp but we already have regular tabs, add camp tab
		$show_camp_tab = false;
		if ( ! $is_camp ) {
			foreach ( $enabled_depts as $dep ) {
				if ( ! empty( $dep['has_summer_camp'] ) && $db->is_camp_period_for_month( $dep['code'], $year, $month ) ) {
					$show_camp_tab = true;
					break;
				}
			}
		}

		ob_start();
		?>
		<div class="meal-tabs">
			<?php foreach ( $type_labels as $t => $label ): ?>
			<a class="meal-tab<?php echo ( ! $is_camp && $type === $t ) ? ' meal-tab--active' : ''; ?>" data-type="<?php echo esc_attr( $t ); ?>" data-year="<?php echo $year; ?>" data-month="<?php echo $month; ?>" data-camp="0" href="<?php echo esc_url( add_query_arg( array( 'meal_type' => $t, 'meal_y' => $year, 'meal_m' => $month ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<?php if ( $show_camp_tab ): ?>
			<a class="meal-tab<?php echo $is_camp ? ' meal-tab--active' : ''; ?>" data-type="<?php echo esc_attr( $type ); ?>" data-year="<?php echo $year; ?>" data-month="<?php echo $month; ?>" data-camp="1" href="<?php echo esc_url( add_query_arg( array( 'meal_type' => $type, 'meal_y' => $year, 'meal_m' => $month, 'meal_camp' => '1' ) ) ); ?>"><?php _e( 'Летний лагерь', 'meal-menu' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="meal-nav-month">
			<a href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $prev_dt->format( 'Y' ), 'meal_m' => $prev_dt->format( 'n' ) ) ) ); ?>" data-year="<?php echo $prev_dt->format( 'Y' ); ?>" data-month="<?php echo $prev_dt->format( 'n' ); ?>">← <?php echo $month_names[ (int) $prev_dt->format( 'n' ) ]; ?></a>
			<h2><?php echo $month_names[ $month ]; ?> <?php echo $year; ?></h2>
			<a href="<?php echo esc_url( add_query_arg( array( 'meal_y' => $next_dt->format( 'Y' ), 'meal_m' => $next_dt->format( 'n' ) ) ) ); ?>" data-year="<?php echo $next_dt->format( 'Y' ); ?>" data-month="<?php echo $next_dt->format( 'n' ); ?>"><?php echo $month_names[ (int) $next_dt->format( 'n' ) ]; ?> →</a>
		</div>

		<div class="meal-cal-scroll"><div class="meal-cal-grid">
			<?php foreach ( array( 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) as $h ): ?>
			<div class="meal-cal-head"><?php echo $h; ?></div>
			<?php endforeach; ?>

			<?php for ( $e = 1; $e < $first_dow; $e++ ): ?>
			<div class="meal-cal-cell empty"></div>
			<?php endfor;

			for ( $d = 1; $d <= $days_in_month; $d++ ):
				$date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $d );
				$dow        = (int) ( new \DateTimeImmutable( $date_str ) )->format( 'N' );
				$is_weekend = $dow >= 6;
				$entry      = $cal_rows[ $date_str ] ?? null;
				$has_menu   = $entry && $entry['template_id'] !== null;
				$is_holiday = $entry && $entry['template_id'] === null;

				$vac_day = $vacation_days[ $date_str ] ?? null;
				$is_vacation = $vac_day && empty( $vac_day['is_holiday'] );
				$is_holiday_entry = $vac_day && ! empty( $vac_day['is_holiday'] );

				$classes = array( 'meal-cal-cell' );
				if ( $is_vacation ) $classes[] = 'vacation';
				elseif ( $has_menu ) $classes[] = 'has-menu';
				elseif ( $is_holiday_entry ) $classes[] = 'holiday';
				elseif ( $is_holiday ) $classes[] = 'holiday';
				elseif ( $is_weekend ) $classes[] = 'weekend';
				if ( $date_str === $today ) $classes[] = 'today';
			?>
			<div class="<?php echo implode( ' ', $classes ); ?>">
				<div class="meal-cal-day"><?php echo $d; ?></div>
				<?php if ( $has_menu ): ?>
				<a class="meal-cal-link meal-menu-trigger" href="#" data-date="<?php echo esc_attr( $date_str ); ?>" data-type="<?php echo esc_attr( $real_type ?? $type ); ?>" data-camp="<?php echo $is_camp ? '1' : '0'; ?>"><?php echo esc_html( $entry['label'] ?? __( 'Меню', 'meal-menu' ) ); ?></a>
				<?php elseif ( $is_holiday ): ?>
				<div class="meal-holiday-label"><?php _e( 'Выходной', 'meal-menu' ); ?></div>
				<?php endif; ?>
			</div>
			<?php endfor;

			$last_dow = (int) ( new \DateTimeImmutable( "$year-$month-$days_in_month" ) )->format( 'N' );
			for ( $e = $last_dow + 1; $e <= 7; $e++ ): ?>
			<div class="meal-cal-cell empty"></div>
			<?php endfor; ?>
		</div></div>

		<div class="meal-legend">
			<div class="meal-legend-item"><div class="meal-legend-dot has-menu"></div> <?php _e( 'Меню опубликовано', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot no-menu"></div> <?php _e( 'Нет данных', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot holiday"></div> <?php _e( 'Выходной / праздник', 'meal-menu' ); ?></div>
			<div class="meal-legend-item"><div class="meal-legend-dot vacation"></div> <?php _e( 'Каникулы', 'meal-menu' ); ?></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function ajax_get_calendar(): void {
		$type   = sanitize_key( $_GET['meal_type'] ?? '' );
		$year   = (int) ( $_GET['meal_y'] ?? 0 );
		$month  = (int) ( $_GET['meal_m'] ?? 0 );
		$is_camp = ! empty( $_GET['meal_camp'] );

		if ( ! $type || ! $year || ! $month || $month < 1 || $month > 12 ) {
			wp_send_json( array( 'ok' => false, 'html' => '' ) );
		}

		$html = self::render_calendar_body( $type, $year, $month, $is_camp );
		wp_send_json( array( 'ok' => true, 'html' => $html ) );
	}

	public function shortcode_day( array $atts = array(), string $content = '' ): string {
		ob_start();
		$atts = shortcode_atts( array( 'date' => '', 'type' => '' ), $atts );
		require MEAL_MENU_DIR . 'templates/public/day.php';
		return ob_get_clean();
	}

	public static function render_oc_content(): string {
		$db  = \Meal_Menu\DB::instance();
		$oc  = $db->get_oc_monitoring();

		$waste_labels = array( 'none' => '', '20' => '&#60;&#160;20%', '30' => '20–30%', '40' => '30–40%', '50' => '&#62;&#160;50%' );

		$has_any = false;
		foreach ( array( 's1_url', 's2_hotline', 's2_chat_url', 's2_forum_url',
			's3_diet1_type', 's3_diet1_url', 's3_diet2_type', 's3_diet2_url',
			's3_diet3_type', 's3_diet3_url', 's3_diet4_type', 's3_diet4_url',
			's4_survey_url', 's4_results_url', 's5_page_url', 's5_materials_url',
			's6_acts_url', 's6_photos_url', 's7_waste_level' ) as $k ) {
			if ( ! empty( $oc[ $k ] ) ) { $has_any = true; break; }
		}
		if ( ! $has_any ) return '';

		ob_start();
		?>
		<details class="meal-oc-spoiler" style="margin-top:24px">
			<summary class="meal-oc-summary" style="font-size:.9rem;text-transform:uppercase;letter-spacing:.1em;color:var(--meal-primary);cursor:pointer;padding:8px 0;border-bottom:2px solid var(--meal-primary);margin-bottom:16px"><?php _e( 'Общественный контроль питания', 'meal-menu' ); ?></summary>

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
		</details>
		<?php
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
		$date    = $_GET['date'] ?? '';
		$type    = $_GET['type'] ?? '';
		$_GET['camp'] = ! empty( $_GET['camp'] ) ? '1' : '';
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

		$type    = sanitize_key( $_POST['type'] ?? 'sm' );
		$is_camp = ! empty( $_POST['camp'] );
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

			$db     = DB::instance();
			$tpl_id = $is_camp ? $db->add_camp_template( $type ) : $db->add_template( $type );

			$dept = $db->get_department( $type );
			if ( $is_camp && $dept && ! empty( $dept['camp_is_boarding'] ) ) {
				$db->set_camp_template_boarding( $tpl_id, 1 );
			} elseif ( ! $is_camp && $dept && ! empty( $dept['is_boarding'] ) ) {
				$db->set_template_boarding( $tpl_id, 1 );
			}

			if ( $is_camp ) {
				$db->save_camp_template_items( $tpl_id, $items );
			} else {
				$db->save_template_items( $tpl_id, $items );
			}

			wp_send_json( array( 'ok' => true, 'id' => $tpl_id ) );
		} catch ( \Exception $e ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка обработки файла', 'meal-menu' ) ) );
		}
	}

	public function ajax_import_tm(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$type    = sanitize_key( $_POST['type'] ?? 'sm' );
		$is_camp = ! empty( $_POST['camp'] );

		if ( ! isset( $_FILES['tm_xlsx'] ) || $_FILES['tm_xlsx']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка загрузки файла', 'meal-menu' ) ) );
		}

		$allowed = array(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/zip',
			'application/octet-stream',
		);
		if ( ! in_array( mime_content_type( $_FILES['tm_xlsx']['tmp_name'] ), $allowed, true ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный формат файла', 'meal-menu' ) ) );
		}

		try {
			$days = \Meal_Menu\Importer_TM::parse( $_FILES['tm_xlsx']['tmp_name'] );
			if ( empty( $days ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'В файле нет данных', 'meal-menu' ) ) );
			}

			$db   = DB::instance();
			$dept = $db->get_department( $type );

			// Remove existing templates for clean import
			$existing = $is_camp ? $db->get_camp_templates( $type ) : $db->get_templates( $type );
			foreach ( $existing as $tpl ) {
				if ( $is_camp ) {
					$db->delete_camp_template( (int) $tpl['id'] );
				} else {
					$db->delete_template( (int) $tpl['id'] );
				}
			}

			$imported = 0;
			ksort( $days );
			foreach ( $days as $items ) {
				$tpl_id = $is_camp ? $db->add_camp_template( $type ) : $db->add_template( $type );

				if ( $is_camp && $dept && ! empty( $dept['camp_is_boarding'] ) ) {
					$db->set_camp_template_boarding( $tpl_id, 1 );
				} elseif ( ! $is_camp && $dept && ! empty( $dept['is_boarding'] ) ) {
					$db->set_template_boarding( $tpl_id, 1 );
				}

				if ( $is_camp ) {
					$db->save_camp_template_items( $tpl_id, $items );
				} else {
					$db->save_template_items( $tpl_id, $items );
				}
				$imported++;
			}

			wp_send_json( array( 'ok' => true, 'imported' => $imported ) );
		} catch ( \Exception $e ) {
			wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
		}
	}

	public function ajax_bulk_delete_templates(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$db      = DB::instance();
		$is_camp = ! empty( $data['camp'] );
		$action  = $data['action'] ?? '';

		if ( $action === 'delete' ) {
			$id = (int) ( $data['id'] ?? 0 );
			if ( $id ) {
				if ( $is_camp ) {
					$db->delete_camp_template( $id );
				} else {
					$db->delete_template( $id );
				}
			}
			wp_send_json( array( 'ok' => true ) );
		}

		if ( $action === 'bulk_delete' ) {
			$ids = $data['ids'] ?? array();
			if ( ! is_array( $ids ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный запрос', 'meal-menu' ) ) );
			}
			$deleted = 0;
			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( $id < 1 ) continue;
				if ( $is_camp ) {
					$db->delete_camp_template( $id );
				} else {
					$db->delete_template( $id );
				}
				$deleted++;
			}
			wp_send_json( array( 'ok' => true, 'deleted' => $deleted ) );
		}

		wp_send_json( array( 'ok' => false, 'error' => __( 'Неизвестное действие', 'meal-menu' ) ) );
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

		if ( $tpl['school_type'] === 'sm' && ! $is_camp ) {
			$approve_date = trim( $_POST['tm_approve_date'] ?? '' );
			if ( $approve_date ) {
				$db->save_tm_approve_date( $approve_date );
			}
			if ( class_exists( '\Meal_Menu\Excel_TM' ) ) {
				\Meal_Menu\Excel_TM::generate( 'sm', (int) current_time( 'Y' ) );
			}
		}
		if ( $is_camp && class_exists( '\Meal_Menu\Excel_TM' ) ) {
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
		$tables = array( 'templates', 'items', 'calendar', 'camp_templates', 'camp_items', 'camp_calendar', 'kitchen_settings', 'departments', 'vacations', 'oc_monitoring', 'users', 'email_tokens' );

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
		if ( $table_exists( $p . 'vacations' ) && ! $has_column( $p . 'vacations', 'actual_date' ) ) {
			$wpdb->query( "ALTER TABLE {$p}vacations ADD COLUMN actual_date date NULL default NULL" );
		}
		if ( $table_exists( $p . 'calendar' ) && ! $has_column( $p . 'calendar', 'iterate_number' ) ) {
			$wpdb->query( "ALTER TABLE {$p}calendar ADD COLUMN iterate_number integer NOT NULL DEFAULT 0" );
		}
		// Camp tables — use MySQL-compatible syntax (WP SQLite plugin translates it)
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
